<?php

namespace App\Modules\Ujian\Services;

use App\Models\BagianUjian;
use App\Models\Ujian;
use App\Modules\DataInduk\Repositories\Contracts\BagianSoalRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\BagianUjianRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\SoalRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LayananBagianUjian
{
    public function __construct(
        private BagianUjianRepositoryInterface $bagianRepo,
        private SoalRepositoryInterface $soalRepo,
        private BagianSoalRepositoryInterface $bagianSoalRepo,
    ) {}

    public function buat(Ujian $ujian, array $data): BagianUjian
    {
        return $this->bagianRepo->create([...$data, 'ujian_id' => $ujian->id]);
    }

    public function buatDenganIsiOtomatis(Ujian $ujian, array $data): int
    {
        return DB::transaction(function () use ($ujian, $data) {
            $bagian = $this->buat($ujian, $data);

            return $this->lampirkanSoalOtomatis($ujian, $bagian);
        });
    }

    public function perbarui(Ujian $ujian, BagianUjian $bagian, array $data): BagianUjian
    {
        $this->pastikanMilikUjian($ujian, $bagian);

        return $this->bagianRepo->update($bagian, $data);
    }

    public function hapus(Ujian $ujian, BagianUjian $bagian): void
    {
        $this->pastikanMilikUjian($ujian, $bagian);

        $this->bagianRepo->delete($bagian);
    }

    public function simpanSusunan(Ujian $ujian, BagianUjian $bagian, array $urutanBagianSoal, array $urutanSoal): void
    {
        $this->pastikanMilikUjian($ujian, $bagian);

        DB::transaction(function () use ($bagian, $urutanBagianSoal, $urutanSoal) {
            if (! empty($urutanBagianSoal)) {
                $bagianSoalIds = $this->bagianSoalRepo->idDenganBankDanKeterampilan($bagian->bank_soal_id, $bagian->keterampilan_id);

                foreach ($urutanBagianSoal as $bagianSoal) {
                    if (! in_array($bagianSoal['bagian_soal_id'], $bagianSoalIds, true)) {
                        throw ValidationException::withMessages(['urutan_bagian_soal' => 'Bagian soal tidak valid untuk bagian ujian ini.']);
                    }

                    $this->bagianRepo->perbaruiAtauBuatPivotBagianSoal($bagian->id, $bagianSoal['bagian_soal_id'], $bagianSoal['urutan']);
                }

                $this->nomoriUlangBagian($bagian);
            }

            if (! empty($urutanSoal)) {
                $this->pastikanNomorUnik($urutanSoal, 'urutan_soal');
                $this->pastikanSoalDalamBagian($bagian, $urutanSoal, 'urutan_soal');
                $this->pastikanSusunanSoalLengkap($bagian, $urutanSoal);
                $this->pastikanSoalTetapDalamBagianSoal($bagian, $urutanSoal);

                foreach ($urutanSoal as $soal) {
                    $this->bagianRepo->perbaruiUrutanSoal($bagian->id, $soal['soal_id'], $soal['nomor']);
                }
            }
        });
    }

    public function susunUlangSoal(Ujian $ujian, BagianUjian $bagian, array $urutan): void
    {
        $this->pastikanMilikUjian($ujian, $bagian);

        $this->pastikanNomorUnik($urutan, 'urutan');
        $this->pastikanSoalDalamBagian($bagian, $urutan, 'urutan');
        $this->pastikanSusunanSoalLengkap($bagian, $urutan);
        $this->pastikanSoalTetapDalamBagianSoal($bagian, $urutan);

        DB::transaction(function () use ($bagian, $urutan) {
            foreach ($urutan as $soal) {
                $this->bagianRepo->perbaruiUrutanSoal($bagian->id, $soal['soal_id'], $soal['nomor']);
            }
        });
    }

    public function susunUlangBagianSoal(Ujian $ujian, BagianUjian $bagian, array $urutan): void
    {
        $this->pastikanMilikUjian($ujian, $bagian);

        $bagianSoalIds = $this->bagianSoalRepo->idDenganBankDanKeterampilan($bagian->bank_soal_id, $bagian->keterampilan_id);

        foreach ($urutan as $bagianSoal) {
            if (! in_array($bagianSoal['bagian_soal_id'], $bagianSoalIds, true)) {
                throw ValidationException::withMessages(['urutan' => 'Bagian soal tidak valid untuk bagian ujian ini.']);
            }
        }

        DB::transaction(function () use ($bagian, $urutan) {
            foreach ($urutan as $bagianSoal) {
                $this->bagianRepo->perbaruiAtauBuatPivotBagianSoal($bagian->id, $bagianSoal['bagian_soal_id'], $bagianSoal['urutan']);
            }

            $this->nomoriUlangBagian($bagian);
        });
    }

    private function lampirkanSoalOtomatis(Ujian $ujian, BagianUjian $bagian): int
    {
        $soalTersedia = $this->soalRepo->soalDenganKeterampilanUntukJenisTes($bagian->keterampilan_id, $ujian->jenis_tes_id, $bagian->bank_soal_id);

        $nomor = 1;
        foreach ($soalTersedia as $soal) {
            $this->bagianRepo->buatAtauTemukanPivotSoal($bagian->id, $soal->id, $nomor++);
        }

        $this->sinkronkanJumlahSoal($bagian);

        return $soalTersedia->count();
    }

    private function sinkronkanJumlahSoal(BagianUjian $bagian): void
    {
        $this->bagianRepo->perbaruiJumlahSoal(
            $bagian,
            $this->bagianRepo->jumlahSoalDalamBagian($bagian->id),
        );
    }

    private function pastikanNomorUnik(array $items, string $kunciKesalahan): void
    {
        $nomor = array_column($items, 'nomor');
        if (count($nomor) !== count(array_unique($nomor))) {
            throw ValidationException::withMessages([$kunciKesalahan => 'Nomor soal harus unik di seluruh bagian ujian.']);
        }
    }

    private function pastikanSoalDalamBagian(BagianUjian $bagian, array $items, string $kunciKesalahan): void
    {
        $idSoalBagian = $this->bagianRepo->idSoalDalamBagian($bagian->id);

        foreach ($items as $soal) {
            if (! in_array($soal['soal_id'], $idSoalBagian, true)) {
                throw ValidationException::withMessages([$kunciKesalahan => 'Soal tidak terpasang di bagian ujian ini.']);
            }
        }
    }

    private function pastikanSusunanSoalLengkap(BagianUjian $bagian, array $items): void
    {
        $idSoalSaatIni = $this->bagianRepo->idSoalDalamBagian($bagian->id);
        $idSoalDiminta = array_column($items, 'soal_id');

        sort($idSoalSaatIni);
        sort($idSoalDiminta);

        if ($idSoalSaatIni !== $idSoalDiminta) {
            throw ValidationException::withMessages([
                'urutan_soal' => 'Susunan soal harus memuat seluruh soal di bagian ujian ini.',
            ]);
        }
    }

    private function pastikanSoalTetapDalamBagianSoal(BagianUjian $bagian, array $items): void
    {
        $barisSoal = $this->bagianRepo->barisSoalDenganSoal($bagian->id);
        $bagianSoalPerSoal = $barisSoal->mapWithKeys(
            fn ($baris): array => [
                $baris->soal_id => (string) ($baris->soal->bagian_soal_id ?? 'none'),
            ],
        );
        $urutanBagianSaatIni = $barisSoal
            ->map(fn ($baris): string => (string) ($baris->soal->bagian_soal_id ?? 'none'))
            ->values()
            ->all();
        $urutanBagianDiminta = collect($items)
            ->map(fn (array $item): string => (string) $bagianSoalPerSoal->get($item['soal_id']))
            ->values()
            ->all();

        if ($urutanBagianSaatIni !== $urutanBagianDiminta) {
            throw ValidationException::withMessages([
                'urutan_soal' => 'Soal tidak boleh dipindahkan ke bagian soal lain.',
            ]);
        }
    }

    private function nomoriUlangBagian(BagianUjian $bagian): void
    {
        $idBagianSoal = $this->bagianRepo->idBagianSoalDalamBagianBerurutan($bagian->id);
        $idBagianSoalBawaan = $this->bagianSoalRepo->idBerurutanDenganBankDanKeterampilan($bagian->bank_soal_id, $bagian->keterampilan_id);

        $urutanBagianSoal = array_values(array_unique(array_merge($idBagianSoal, $idBagianSoalBawaan)));

        $baris = $this->bagianRepo->barisSoalDenganSoal($bagian->id);

        $peringkat = function ($barisSoal) use ($urutanBagianSoal) {
            $bagianSoalId = $barisSoal->soal->bagian_soal_id;
            if (! $bagianSoalId) {
                return 999999;
            }

            $index = array_search($bagianSoalId, $urutanBagianSoal);

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
            $this->bagianRepo->perbaruiUrutanPivotSoal($barisSoal->id, $nomor++);
        }
    }

    private function pastikanMilikUjian(Ujian $ujian, BagianUjian $bagian): void
    {
        if ($bagian->ujian_id !== $ujian->id) {
            throw (new ModelNotFoundException)->setModel(BagianUjian::class, [$bagian->id]);
        }
    }
}
