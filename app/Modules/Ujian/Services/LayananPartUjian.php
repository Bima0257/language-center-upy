<?php

namespace App\Modules\Ujian\Services;

use App\Models\PartUjian;
use App\Models\Ujian;
use App\Modules\DataInduk\Repositories\Contracts\PartSoalRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\PartUjianRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\SoalRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LayananPartUjian
{
    public function __construct(
        private PartUjianRepositoryInterface $partRepo,
        private SoalRepositoryInterface $soalRepo,
        private PartSoalRepositoryInterface $partSoalRepo,
    ) {}

    public function buat(Ujian $ujian, array $data): PartUjian
    {
        $this->pastikanUjianTidakAktif($ujian);

        return $this->partRepo->create([...$data, 'ujian_id' => $ujian->id]);
    }

    public function buatDenganIsiOtomatis(Ujian $ujian, array $data): int
    {
        return DB::transaction(function () use ($ujian, $data) {
            $part = $this->buat($ujian, $data);

            return $this->lampirkanSoalOtomatis($ujian, $part);
        });
    }

    public function perbarui(Ujian $ujian, PartUjian $part, array $data): PartUjian
    {
        $this->pastikanMilikUjian($ujian, $part);
        $this->pastikanUjianTidakAktif($ujian);

        return $this->partRepo->update($part, $data);
    }

    public function hapus(Ujian $ujian, PartUjian $part): void
    {
        $this->pastikanMilikUjian($ujian, $part);
        $this->pastikanUjianTidakAktif($ujian);

        $this->partRepo->delete($part);
    }

    public function simpanSusunan(Ujian $ujian, PartUjian $part, array $urutanPartSoal, array $urutanSoal): void
    {
        $this->pastikanMilikUjian($ujian, $part);
        $this->pastikanUjianTidakAktif($ujian);

        DB::transaction(function () use ($part, $urutanPartSoal, $urutanSoal) {
            if (! empty($urutanPartSoal)) {
                $partSoalIds = $this->partSoalRepo->idDenganBankDanSection($part->bank_soal_id, $part->section_id);

                foreach ($urutanPartSoal as $partSoal) {
                    if (! in_array($partSoal['part_soal_id'], $partSoalIds, true)) {
                        throw ValidationException::withMessages(['urutan_part_soal' => 'Part soal tidak valid untuk part ujian ini.']);
                    }

                    $this->partRepo->perbaruiAtauBuatPivotPartSoal($part->id, $partSoal['part_soal_id'], $partSoal['urutan']);
                }

                $this->nomoriUlangPart($part);
            }

            if (! empty($urutanSoal)) {
                $this->pastikanNomorUnik($urutanSoal, 'urutan_soal');
                $this->pastikanSoalDalamPart($part, $urutanSoal, 'urutan_soal');
                $this->pastikanSusunanSoalLengkap($part, $urutanSoal);
                $this->pastikanSoalTetapDalamPartSoal($part, $urutanSoal);

                foreach ($urutanSoal as $soal) {
                    $this->partRepo->perbaruiUrutanSoal($part->id, $soal['soal_id'], $soal['nomor']);
                }
            }
        });
    }

    public function susunUlangSoal(Ujian $ujian, PartUjian $part, array $urutan): void
    {
        $this->pastikanMilikUjian($ujian, $part);
        $this->pastikanUjianTidakAktif($ujian);

        $this->pastikanNomorUnik($urutan, 'urutan');
        $this->pastikanSoalDalamPart($part, $urutan, 'urutan');
        $this->pastikanSusunanSoalLengkap($part, $urutan);
        $this->pastikanSoalTetapDalamPartSoal($part, $urutan);

        DB::transaction(function () use ($part, $urutan) {
            foreach ($urutan as $soal) {
                $this->partRepo->perbaruiUrutanSoal($part->id, $soal['soal_id'], $soal['nomor']);
            }
        });
    }

    public function susunUlangPartSoal(Ujian $ujian, PartUjian $part, array $urutan): void
    {
        $this->pastikanMilikUjian($ujian, $part);
        $this->pastikanUjianTidakAktif($ujian);

        $partSoalIds = $this->partSoalRepo->idDenganBankDanSection($part->bank_soal_id, $part->section_id);

        foreach ($urutan as $partSoal) {
            if (! in_array($partSoal['part_soal_id'], $partSoalIds, true)) {
                throw ValidationException::withMessages(['urutan' => 'Part soal tidak valid untuk part ujian ini.']);
            }
        }

        DB::transaction(function () use ($part, $urutan) {
            foreach ($urutan as $partSoal) {
                $this->partRepo->perbaruiAtauBuatPivotPartSoal($part->id, $partSoal['part_soal_id'], $partSoal['urutan']);
            }

            $this->nomoriUlangPart($part);
        });
    }

    private function lampirkanSoalOtomatis(Ujian $ujian, PartUjian $part): int
    {
        $soalTersedia = $this->soalRepo->soalDenganSectionUntukProgramTes($part->section_id, $ujian->program_tes_id, $part->bank_soal_id);

        $nomor = 1;
        foreach ($soalTersedia as $soal) {
            $this->partRepo->buatAtauTemukanPivotSoal($part->id, $soal->id, $nomor++);
        }

        $this->sinkronkanJumlahSoal($part);

        return $soalTersedia->count();
    }

    private function sinkronkanJumlahSoal(PartUjian $part): void
    {
        $this->partRepo->perbaruiJumlahSoal(
            $part,
            $this->partRepo->jumlahSoalDalamPart($part->id),
        );
    }

    private function pastikanNomorUnik(array $items, string $kunciKesalahan): void
    {
        $nomor = array_column($items, 'nomor');
        if (count($nomor) !== count(array_unique($nomor))) {
            throw ValidationException::withMessages([$kunciKesalahan => 'Nomor soal harus unik di seluruh part ujian.']);
        }
    }

    private function pastikanSoalDalamPart(PartUjian $part, array $items, string $kunciKesalahan): void
    {
        $idSoalPart = $this->partRepo->idSoalDalamPart($part->id);

        foreach ($items as $soal) {
            if (! in_array($soal['soal_id'], $idSoalPart, true)) {
                throw ValidationException::withMessages([$kunciKesalahan => 'Soal tidak terpasang di part ujian ini.']);
            }
        }
    }

    private function pastikanSusunanSoalLengkap(PartUjian $part, array $items): void
    {
        $idSoalSaatIni = $this->partRepo->idSoalDalamPart($part->id);
        $idSoalDiminta = array_column($items, 'soal_id');

        sort($idSoalSaatIni);
        sort($idSoalDiminta);

        if ($idSoalSaatIni !== $idSoalDiminta) {
            throw ValidationException::withMessages([
                'urutan_soal' => 'Susunan soal harus memuat seluruh soal di part ujian ini.',
            ]);
        }
    }

    private function pastikanSoalTetapDalamPartSoal(PartUjian $part, array $items): void
    {
        $barisSoal = $this->partRepo->barisSoalDenganSoal($part->id);
        $partSoalPerSoal = $barisSoal->mapWithKeys(
            fn ($baris): array => [
                $baris->soal_id => (string) ($baris->soal->part_soal_id ?? 'none'),
            ],
        );
        $urutanPartSaatIni = $barisSoal
            ->map(fn ($baris): string => (string) ($baris->soal->part_soal_id ?? 'none'))
            ->values()
            ->all();
        $urutanPartDiminta = collect($items)
            ->map(fn (array $item): string => (string) $partSoalPerSoal->get($item['soal_id']))
            ->values()
            ->all();

        if ($urutanPartSaatIni !== $urutanPartDiminta) {
            throw ValidationException::withMessages([
                'urutan_soal' => 'Soal tidak boleh dipindahkan ke part soal lain.',
            ]);
        }
    }

    private function nomoriUlangPart(PartUjian $part): void
    {
        $idPartSoal = $this->partRepo->idPartSoalDalamPartBerurutan($part->id);
        $idPartSoalBawaan = $this->partSoalRepo->idBerurutanDenganBankDanSection($part->bank_soal_id, $part->section_id);

        $urutanPartSoal = array_values(array_unique(array_merge($idPartSoal, $idPartSoalBawaan)));

        $baris = $this->partRepo->barisSoalDenganSoal($part->id);

        $peringkat = function ($barisSoal) use ($urutanPartSoal) {
            $partSoalId = $barisSoal->soal->part_soal_id;
            if (! $partSoalId) {
                return 999999;
            }

            $index = array_search($partSoalId, $urutanPartSoal);

            return $index === false ? 999998 : $index;
        };

        $baris = $baris->sort(function ($a, $b) use ($peringkat) {
            $peringkatA = $peringkat($a);
            $peringkatB = $peringkat($b);

            if ($peringkatA !== $peringkatB) {
                return $peringkatA <=> $peringkatB;
            }

            return $a->urutan <=> $b->urutan;
        });

        $nomor = 1;
        foreach ($baris as $barisSoal) {
            $this->partRepo->perbaruiUrutanPivotSoal($barisSoal->id, $nomor++);
        }
    }

    private function pastikanMilikUjian(Ujian $ujian, PartUjian $part): void
    {
        if ($part->ujian_id !== $ujian->id) {
            throw (new ModelNotFoundException)->setModel(PartUjian::class, [$part->id]);
        }
    }

    private function pastikanUjianTidakAktif(Ujian $ujian): void
    {
        if ($ujian->aktif) {
            throw new \RuntimeException('Ujian aktif tidak dapat diubah. Nonaktifkan ujian terlebih dahulu.');
        }
    }
}
