<?php

namespace App\Modules\PelaksanaanUjian\Services;

use App\Models\SesiUjian;
use App\Modules\Ujian\Repositories\Contracts\BagianUjianRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\SoalRepositoryInterface;
use App\Modules\Ujian\Services\LayananKebijakanMateriSoal;
use Illuminate\Database\Eloquent\Collection;

class LayananPelaksanaanUjian
{
    public function __construct(
        private BagianUjianRepositoryInterface $bagianRepo,
        private SoalRepositoryInterface $soalRepo,
        private LayananKebijakanMateriSoal $kebijakanMateri,
    ) {}

    public function soalUntukSesi(SesiUjian $sesi): Collection
    {
        $ujian = $sesi->slotUjian?->periodeUjian?->ujian;
        $bagianUjian = $ujian !== null ? $ujian->bagianUjian : new Collection;

        $barisPivot = $this->bagianRepo->barisPivotDenganIdBagian($bagianUjian->pluck('id'));
        $idSoalPivot = $barisPivot->pluck('soal_id');

        $soal = new Collection;

        if ($barisPivot->isNotEmpty()) {
            $soal = $this->soalRepo->temukanDenganIdDenganMateriSoal($idSoalPivot->all())
                ->each(function ($dataSoal) use ($barisPivot) {
                    $baris = $barisPivot->firstWhere('soal_id', $dataSoal->id);
                    $dataSoal->urutan = $baris !== null ? $baris->urutan : $dataSoal->urutan;
                })
                ->sortBy('urutan')
                ->values();
        }

        $bagianKosong = $bagianUjian->filter(fn ($bagian) => ! $barisPivot->where('bagian_ujian_id', $bagian->id)->count());

        if ($bagianKosong->isNotEmpty()) {
            $keterampilanIds = $bagianKosong->pluck('keterampilan_id')->filter()->unique()->values()->all();
            $bankIds = $bagianKosong->pluck('bank_soal_id')->filter()->unique()->all();

            $cadangan = $this->soalRepo->cadanganDisetujuiDenganKeterampilan(
                $keterampilanIds,
                $ujian?->jenis_tes_id,
                count($bankIds) === 1 ? $bankIds[0] : null,
                $idSoalPivot->all(),
            );

            $soal = $soal->merge($cadangan);
        }

        return $soal->each(function ($dataSoal) {
            $materi = $this->kebijakanMateri->materiUntuk(
                $dataSoal->bank_soal_id,
                $dataSoal->keterampilan_id,
            );

            if ($materi !== null) {
                $dataSoal->materi = $materi;
            }

            $dataSoal->makeHidden('kunci_jawaban');
        });
    }
}
