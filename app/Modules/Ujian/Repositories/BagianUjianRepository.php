<?php

namespace App\Modules\Ujian\Repositories;

use App\Models\BagianSoalUjian;
use App\Models\BagianUjian;
use App\Models\SoalBagianUjian;
use App\Models\Ujian;
use App\Modules\Ujian\Repositories\Contracts\BagianUjianRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

class BagianUjianRepository implements BagianUjianRepositoryInterface
{
    public function create(array $data): BagianUjian
    {
        return BagianUjian::create($data);
    }

    /**
     * @return Collection<int, BagianUjian>
     */
    public function semuaUntukUjian(Ujian $ujian): Collection
    {
        return BagianUjian::where('ujian_id', $ujian->id)
            ->orderBy('urutan')
            ->get();
    }

    public function update(BagianUjian $bagian, array $data): BagianUjian
    {
        $bagian->update($data);

        return $bagian->fresh();
    }

    public function delete(BagianUjian $bagian): void
    {
        $bagian->delete();
    }

    public function soalDikelompokkanDenganBagian(BaseCollection $bagianIds): BaseCollection
    {
        return SoalBagianUjian::with(['soal.materiSoal', 'soal.bagianSoal'])
            ->whereIn('bagian_ujian_id', $bagianIds)
            ->get()
            ->groupBy('bagian_ujian_id');
    }

    public function bagianSoalDikelompokkanDenganBagian(BaseCollection $bagianIds): BaseCollection
    {
        return BagianSoalUjian::whereIn('bagian_ujian_id', $bagianIds)
            ->get()
            ->groupBy('bagian_ujian_id');
    }

    /**
     * @return Collection<int, SoalBagianUjian>
     */
    public function barisPivotDenganIdBagian(BaseCollection $bagianIds): Collection
    {
        return SoalBagianUjian::whereIn('bagian_ujian_id', $bagianIds)->get();
    }

    public function buatAtauTemukanPivotSoal(string $bagianId, string $soalId, int $urutan): void
    {
        SoalBagianUjian::firstOrCreate(
            ['bagian_ujian_id' => $bagianId, 'soal_id' => $soalId],
            ['urutan' => $urutan],
        );
    }

    public function buatPivotSoal(string $bagianId, string $soalId, int $urutan): void
    {
        SoalBagianUjian::create([
            'bagian_ujian_id' => $bagianId,
            'soal_id' => $soalId,
            'urutan' => $urutan,
        ]);
    }

    /**
     * @return array<string>
     */
    public function idSoalDalamBagian(string $bagianId): array
    {
        return SoalBagianUjian::where('bagian_ujian_id', $bagianId)->pluck('soal_id')->all();
    }

    public function urutanSoalBerikutnya(string $bagianId): int
    {
        return (SoalBagianUjian::where('bagian_ujian_id', $bagianId)->max('urutan') ?? 0) + 1;
    }

    public function perbaruiUrutanSoal(string $bagianId, string $soalId, int $urutan): void
    {
        SoalBagianUjian::where('bagian_ujian_id', $bagianId)
            ->where('soal_id', $soalId)
            ->update(['urutan' => $urutan]);
    }

    public function perbaruiAtauBuatPivotBagianSoal(string $bagianId, string $bagianSoalId, int $urutan): void
    {
        BagianSoalUjian::updateOrCreate(
            ['bagian_ujian_id' => $bagianId, 'bagian_soal_id' => $bagianSoalId],
            ['urutan' => $urutan],
        );
    }

    /**
     * @return array<string>
     */
    public function idBagianSoalDalamBagianBerurutan(string $bagianId): array
    {
        return BagianSoalUjian::where('bagian_ujian_id', $bagianId)
            ->orderBy('urutan')
            ->pluck('bagian_soal_id')
            ->all();
    }

    /**
     * @return Collection<int, SoalBagianUjian>
     */
    public function barisSoalDenganSoal(string $bagianId): Collection
    {
        return SoalBagianUjian::with('soal')
            ->where('bagian_ujian_id', $bagianId)
            ->orderBy('urutan')
            ->get();
    }

    public function perbaruiUrutanPivotSoal(string $barisId, int $urutan): void
    {
        SoalBagianUjian::whereKey($barisId)->update(['urutan' => $urutan]);
    }

    public function jumlahSoalDalamBagian(string $bagianId): int
    {
        return SoalBagianUjian::where('bagian_ujian_id', $bagianId)->count();
    }

    public function perbaruiJumlahSoal(BagianUjian $bagian, int $jumlah): void
    {
        $bagian->update(['jumlah_soal' => $jumlah]);
    }
}
