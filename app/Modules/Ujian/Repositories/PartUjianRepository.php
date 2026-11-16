<?php

namespace App\Modules\Ujian\Repositories;

use App\Models\PartSoal;
use App\Models\PartSoalUjian;
use App\Models\PartUjian;
use App\Models\SoalPartUjian;
use App\Models\Ujian;
use App\Modules\Ujian\Repositories\Contracts\PartUjianRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Str;

class PartUjianRepository implements PartUjianRepositoryInterface
{
    public function create(array $data): PartUjian
    {
        return PartUjian::create($data);
    }

    /**
     * @return Collection<int, PartUjian>
     */
    public function semuaUntukUjian(Ujian $ujian): Collection
    {
        return PartUjian::where('ujian_id', $ujian->id)
            ->orderBy('urutan')
            ->get();
    }

    public function update(PartUjian $part, array $data): PartUjian
    {
        $part->update($data);

        return $part->fresh();
    }

    public function delete(PartUjian $part): void
    {
        $part->delete();
    }

    public function soalDikelompokkanDenganPart(BaseCollection $partIds): BaseCollection
    {
        return SoalPartUjian::with(['soal.materiSoal.mediaMateriSoal', 'soal.partSoal'])
            ->whereIn('part_ujian_id', $partIds)
            ->orderBy('part_ujian_id')
            ->orderBy('urutan')
            ->get()
            ->groupBy('part_ujian_id');
    }

    public function partSoalDikelompokkanDenganPart(BaseCollection $partIds): BaseCollection
    {
        return PartSoalUjian::whereIn('part_ujian_id', $partIds)
            ->get()
            ->groupBy('part_ujian_id');
    }

    /**
     * @param  array<string>  $partSoalIds
     * @return array<string>
     */
    public function idPartSoalDalamUjianAktif(array $partSoalIds): array
    {
        if ($partSoalIds === []) {
            return [];
        }

        return PartSoalUjian::whereIn('part_soal_id', $partSoalIds)
            ->whereHas('partUjian.ujian', fn ($query) => $query->where('aktif', true))
            ->distinct()
            ->pluck('part_soal_id')
            ->all();
    }

    /**
     * @param  array<string>  $partSoalIds
     */
    public function hapusPartSoalDalamUjianNonaktif(array $partSoalIds): void
    {
        if ($partSoalIds === []) {
            return;
        }

        PartSoalUjian::whereIn('part_soal_id', $partSoalIds)
            ->whereHas('partUjian.ujian', fn ($query) => $query->where('aktif', false))
            ->delete();
    }

    /**
     * @param  Collection<int, PartSoal>  $partSoal
     */
    public function sinkronkanPartSoalDalamUjianNonaktif(Collection $partSoal): void
    {
        $partSoalAktif = $partSoal
            ->filter(fn (PartSoal $part): bool => $part->aktif === true)
            ->values();

        if ($partSoalAktif->isEmpty()) {
            return;
        }

        $pasanganBankDanSection = $partSoalAktif
            ->mapWithKeys(fn (PartSoal $part): array => [
                $part->bank_soal_id.'|'.$part->section_id => [
                    'bank_soal_id' => $part->bank_soal_id,
                    'section_id' => $part->section_id,
                ],
            ])
            ->values()
            ->all();

        $partUjian = PartUjian::whereHas('ujian', fn ($query) => $query->where('aktif', false))
            ->where(function ($query) use ($pasanganBankDanSection): void {
                foreach ($pasanganBankDanSection as $pasangan) {
                    $query->orWhere(function ($query) use ($pasangan): void {
                        $query
                            ->where('bank_soal_id', $pasangan['bank_soal_id'])
                            ->where('section_id', $pasangan['section_id']);
                    });
                }
            })
            ->get(['id', 'bank_soal_id', 'section_id']);

        if ($partUjian->isEmpty()) {
            return;
        }

        $barisPivot = [];
        foreach ($partSoalAktif as $part) {
            foreach ($partUjian as $partUjianItem) {
                if ($partUjianItem->bank_soal_id !== $part->bank_soal_id
                    || $partUjianItem->section_id !== $part->section_id) {
                    continue;
                }

                $barisPivot[] = [
                    'id' => (string) Str::uuid(),
                    'part_ujian_id' => $partUjianItem->id,
                    'part_soal_id' => $part->id,
                    'urutan' => $part->urutan,
                ];
            }
        }

        if ($barisPivot !== []) {
            PartSoalUjian::insert($barisPivot);
        }
    }

    /**
     * @return Collection<int, SoalPartUjian>
     */
    public function barisPivotDenganIdPart(BaseCollection $partIds): Collection
    {
        return SoalPartUjian::whereIn('part_ujian_id', $partIds)->get();
    }

    public function buatAtauTemukanPivotSoal(string $partId, string $soalId, int $urutan): void
    {
        SoalPartUjian::firstOrCreate(
            ['part_ujian_id' => $partId, 'soal_id' => $soalId],
            ['urutan' => $urutan],
        );
    }

    public function buatPivotSoal(string $partId, string $soalId, int $urutan): void
    {
        SoalPartUjian::create([
            'part_ujian_id' => $partId,
            'soal_id' => $soalId,
            'urutan' => $urutan,
        ]);
    }

    /**
     * @return array<string>
     */
    public function idSoalDalamPart(string $partId): array
    {
        return SoalPartUjian::where('part_ujian_id', $partId)->pluck('soal_id')->all();
    }

    public function urutanSoalBerikutnya(string $partId): int
    {
        return (SoalPartUjian::where('part_ujian_id', $partId)->max('urutan') ?? 0) + 1;
    }

    public function perbaruiUrutanSoal(string $partId, string $soalId, int $urutan): void
    {
        SoalPartUjian::where('part_ujian_id', $partId)
            ->where('soal_id', $soalId)
            ->update(['urutan' => $urutan]);
    }

    public function perbaruiAtauBuatPivotPartSoal(string $partId, string $partSoalId, int $urutan): void
    {
        PartSoalUjian::updateOrCreate(
            ['part_ujian_id' => $partId, 'part_soal_id' => $partSoalId],
            ['urutan' => $urutan],
        );
    }

    /**
     * @return array<string>
     */
    public function idPartSoalDalamPartBerurutan(string $partId): array
    {
        return PartSoalUjian::where('part_ujian_id', $partId)
            ->orderBy('urutan')
            ->pluck('part_soal_id')
            ->all();
    }

    /**
     * @return Collection<int, SoalPartUjian>
     */
    public function barisSoalDenganSoal(string $partId): Collection
    {
        return SoalPartUjian::with('soal')
            ->where('part_ujian_id', $partId)
            ->orderBy('urutan')
            ->get();
    }

    public function perbaruiUrutanPivotSoal(string $barisId, int $urutan): void
    {
        SoalPartUjian::whereKey($barisId)->update(['urutan' => $urutan]);
    }

    public function jumlahSoalDalamPart(string $partId): int
    {
        return SoalPartUjian::where('part_ujian_id', $partId)->count();
    }

    public function perbaruiJumlahSoal(PartUjian $part, int $jumlah): void
    {
        $part->update(['jumlah_soal' => $jumlah]);
    }
}
