<?php

namespace App\Modules\PelaksanaanUjian\Services;

use App\Models\SesiUjian;
use App\Models\Soal;
use App\Models\Ujian;
use App\Modules\Ujian\Repositories\Contracts\PartUjianRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\SoalRepositoryInterface;
use App\Modules\Ujian\Services\LayananKebijakanMateriSoal;
use Illuminate\Database\Eloquent\Collection;

class LayananPelaksanaanUjian
{
    public function __construct(
        private PartUjianRepositoryInterface $partRepo,
        private SoalRepositoryInterface $soalRepo,
        private LayananKebijakanMateriSoal $kebijakanMateri,
    ) {}

    /**
     * @return Collection<int, Soal>
     */
    public function soalUntukSesi(SesiUjian $sesi): Collection
    {
        $ujian = $sesi->slotUjian?->periodeUjian?->ujian;

        if ($ujian === null) {
            return new Collection;
        }

        return $this->kumpulkanSoalUntukUjian($ujian, true)->each(function (Soal $dataSoal): void {
            $dataSoal->makeHidden('kunci_jawaban');
        });
    }

    /**
     * @return Collection<int, Soal>
     */
    public function soalUntukPenilaian(SesiUjian $sesi): Collection
    {
        $ujian = $sesi->slotUjian?->periodeUjian?->ujian;

        if ($ujian === null) {
            return new Collection;
        }

        return $this->kumpulkanSoalUntukUjian($ujian, false);
    }

    /**
     * @return Collection<int, Soal>
     */
    public function soalUntukUjian(Ujian $ujian): Collection
    {
        return $this->kumpulkanSoalUntukUjian($ujian, false);
    }

    /**
     * @return Collection<int, Soal>
     */
    private function kumpulkanSoalUntukUjian(Ujian $ujian, bool $sertakanMateri): Collection
    {
        $partUjian = $ujian->partUjian;

        $barisPivot = $this->partRepo->barisPivotDenganIdPart($partUjian->pluck('id'));
        $idSoalPivot = $barisPivot->pluck('soal_id');

        $soal = new Collection;

        if ($barisPivot->isNotEmpty()) {
            $soal = $this->soalRepo->temukanDenganIdDenganMateriSoal($idSoalPivot->all())
                ->each(function (Soal $dataSoal) use ($barisPivot): void {
                    $baris = $barisPivot->firstWhere('soal_id', $dataSoal->id);
                    $dataSoal->urutan = $baris !== null ? $baris->urutan : $dataSoal->urutan;
                })
                ->sortBy('urutan')
                ->values();
        }

        $partKosong = $partUjian->filter(fn ($part) => ! $barisPivot->where('part_ujian_id', $part->id)->count());

        if ($partKosong->isNotEmpty()) {
            $sectionIds = $partKosong->pluck('section_id')->filter()->unique()->values()->all();
            $bankIds = $partKosong->pluck('bank_soal_id')->filter()->unique()->all();

            $cadangan = $this->soalRepo->cadanganDenganSection(
                $sectionIds,
                $ujian->program_tes_id,
                count($bankIds) === 1 ? $bankIds[0] : null,
                $idSoalPivot->all(),
            );

            $soal = $soal->merge($cadangan);
        }

        if (! $sertakanMateri) {
            return $soal;
        }

        return $soal->each(function (Soal $dataSoal): void {
            $materi = $this->kebijakanMateri->materiUntuk($dataSoal->section_id);

            if ($materi !== null) {
                $dataSoal->materi = $materi;
            }
        });
    }
}
