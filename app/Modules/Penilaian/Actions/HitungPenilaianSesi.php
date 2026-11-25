<?php

namespace App\Modules\Penilaian\Actions;

use App\Models\Jawaban;
use App\Models\SesiUjian;
use App\Models\Soal;
use App\Modules\PelaksanaanUjian\Services\LayananPelaksanaanUjian;
use App\Modules\Penilaian\DTOs\HasilPenilaianSesi;
use App\Modules\Penilaian\Services\LayananInterpretasiSkor;
use Illuminate\Support\Collection;

class HitungPenilaianSesi
{
    public function __construct(
        private LayananPelaksanaanUjian $layananPelaksanaan,
        private HitungSkorSection $hitungSkorSection,
        private HitungSkorTotal $hitungSkorTotal,
        private LayananInterpretasiSkor $layananInterpretasi,
    ) {}

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function execute(SesiUjian $sesi, array $snapshot): HasilPenilaianSesi
    {
        $sesi->loadMissing('jawaban', 'ujian', 'slotUjian.periodeUjian.ujian');

        if (! isset($snapshot['skema_penilaian_id'])) {
            $snapshot['skema_penilaian_id'] = $sesi->skema_penilaian_id;
        }

        return $this->executeDariSoal(
            $this->layananPelaksanaan->soalUntukPenilaian($sesi),
            $sesi->jawaban->keyBy('soal_id'),
            $snapshot,
            true,
        );
    }

    /**
     * @param  Collection<int, Soal>  $soalUjian
     * @param  Collection<string, Jawaban>  $jawabanPerSoal
     * @param  array<string, mixed>  $snapshot
     */
    public function executeDariSoal(
        Collection $soalUjian,
        Collection $jawabanPerSoal,
        array $snapshot,
        bool $simpanStatusJawaban = true,
    ): HasilPenilaianSesi {
        $aturanList = $snapshot['aturan'] ?? [];

        if (! is_array($aturanList) || $aturanList === []) {
            throw new \RuntimeException('Snapshot aturan penilaian tidak memiliki konfigurasi section.');
        }

        $idSectionSkema = collect($aturanList)
            ->pluck('section_id')
            ->filter()
            ->values();

        $idSectionTanpaAturan = $soalUjian
            ->pluck('section_id')
            ->unique()
            ->diff($idSectionSkema);

        if ($idSectionTanpaAturan->isNotEmpty()) {
            throw new \RuntimeException(
                'Terdapat section pada ujian yang belum memiliki aturan penilaian: '.
                $idSectionTanpaAturan->implode(', ').'.',
            );
        }

        $hasilSection = [];
        $skorSection = [];

        foreach ($aturanList as $aturan) {
            $sectionId = (string) ($aturan['section_id'] ?? '');

            if ($sectionId === '') {
                throw new \RuntimeException('Snapshot aturan penilaian tidak memiliki section_id.');
            }

            $soalSection = $soalUjian
                ->filter(fn (Soal $soal): bool => $soal->section_id === $sectionId)
                ->values();

            $hasil = $this->hitungSkorSection->execute(
                $aturan,
                $soalSection,
                $jawabanPerSoal,
                $simpanStatusJawaban,
            );

            $hasilSection[] = $hasil;
            $skorSection[] = $hasil->skorSkala;
        }

        $total = $this->hitungSkorTotal->execute($skorSection);
        $totalMaksimum = (float) ($snapshot['skala_total_maksimum'] ?? collect($aturanList)->sum('skor_skala_maksimum'));

        $interpretasi = $this->layananInterpretasi->cari(
            $snapshot['interpretasi'] ?? [],
            $total,
        );

        return new HasilPenilaianSesi(
            skemaPenilaianId: (string) ($snapshot['skema_penilaian_id'] ?? ''),
            skorSection: $hasilSection,
            total: $total,
            totalMaksimum: round($totalMaksimum, 1),
            lulus: (bool) ($interpretasi['lulus'] ?? false),
            tingkatCefr: isset($interpretasi['tingkat_cefr']) ? (string) $interpretasi['tingkat_cefr'] : null,
            labelTingkat: isset($interpretasi['label_tingkat']) ? (string) $interpretasi['label_tingkat'] : null,
            deskripsiTingkat: isset($interpretasi['deskripsi']) ? (string) $interpretasi['deskripsi'] : null,
        );
    }
}
