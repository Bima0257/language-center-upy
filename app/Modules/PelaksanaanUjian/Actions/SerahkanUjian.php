<?php

namespace App\Modules\PelaksanaanUjian\Actions;

use App\Enums\StatusSesi;
use App\Models\SesiUjian;
use App\Modules\PelaksanaanUjian\DTOs\HasilPenyerahan;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiUjianRepositoryInterface;
use App\Modules\Penilaian\Actions\HitungPenilaianSesi;
use App\Modules\Penilaian\DTOs\HasilPenilaianSesi;
use App\Modules\Penilaian\Repositories\Contracts\HasilPenilaianRepositoryInterface;
use App\Modules\Penilaian\Services\LayananAturanPenilaian;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SerahkanUjian
{
    public function __construct(
        private SesiUjianRepositoryInterface $sesiRepo,
        private HitungPenilaianSesi $hitungPenilaian,
        private HasilPenilaianRepositoryInterface $hasilPenilaianRepo,
        private LayananAturanPenilaian $layananAturan,
    ) {}

    public function execute(string $sesiId): HasilPenyerahan
    {
        $sesi = $this->sesiRepo->temukanDenganJawaban($sesiId);

        if (! $sesi || $sesi->status === StatusSesi::SUBMITTED) {
            throw new \RuntimeException('Ujian sudah disubmit sebelumnya.');
        }

        [$hasil, $skorMembaca, $skorMenyimak] = DB::transaction(function () use ($sesi, $sesiId): array {
            $snapshot = $this->snapshotUntukSesi($sesi);
            $hasil = $this->hitungPenilaian->execute($sesi, $snapshot);

            $this->hasilPenilaianRepo->buatBanyak($this->barisHasilPenilaian($sesiId, $hasil));

            $skorMembaca = $hasil->skorUntukNamaSection('Reading') ?? 0.0;
            $skorMenyimak = $hasil->skorUntukNamaSection('Listening') ?? 0.0;

            $updates = [
                'status' => StatusSesi::SUBMITTED,
                'diserahkan_pada' => now(),
                'skema_penilaian_id' => $hasil->skemaPenilaianId,
                'snapshot_penilaian' => $snapshot,
                'skor_membaca' => $skorMembaca,
                'skor_menyimak' => $skorMenyimak,
                'skor_total' => $hasil->total,
                'skor_total_maksimum' => $hasil->totalMaksimum,
                'lulus' => $hasil->lulus,
                'tingkat_cefr' => $hasil->tingkatCefr,
                'label_tingkat' => $hasil->labelTingkat,
                'deskripsi_tingkat' => $hasil->deskripsiTingkat,
            ];

            $batasPelanggaran = $sesi->batasPelanggaranMaksimum();

            if ($sesi->jumlah_pelanggaran >= $batasPelanggaran) {
                $updates['ditandai'] = true;
                $updates['alasan_penandaan'] = "Pelanggaran mencapai {$batasPelanggaran} strike.";
            }

            $this->sesiRepo->update($sesiId, $updates);

            return [$hasil, $skorMembaca, $skorMenyimak];
        });

        activity()
            ->performedOn($sesi)
            ->withProperties([
                'skema_penilaian_id' => $hasil->skemaPenilaianId,
                'skor_membaca' => $skorMembaca,
                'skor_menyimak' => $skorMenyimak,
                'skor_total' => $hasil->total,
                'skor_total_maksimum' => $hasil->totalMaksimum,
                'lulus' => $hasil->lulus,
                'tingkat_cefr' => $hasil->tingkatCefr,
            ])
            ->log('exam_submitted');

        return new HasilPenyerahan(
            sesiId: $sesiId,
            skorMembaca: $skorMembaca,
            skorMenyimak: $skorMenyimak,
            skorTotal: $hasil->total,
            skorTotalMaksimum: $hasil->totalMaksimum,
            lulus: $hasil->lulus,
            tingkatCefr: $hasil->tingkatCefr,
            labelTingkat: $hasil->labelTingkat,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotUntukSesi(SesiUjian $sesi): array
    {
        $snapshot = $sesi->snapshot_penilaian;

        if (is_array($snapshot) && $snapshot !== []) {
            return $snapshot;
        }

        $programTesId = $sesi->slotUjian?->periodeUjian?->ujian?->program_tes_id;

        if ($programTesId === null) {
            throw new \RuntimeException('Program tes untuk penilaian tidak ditemukan.');
        }

        Log::warning('Sesi ujian tanpa snapshot penilaian, memakai skema aktif saat submit.', [
            'sesi_ujian_id' => $sesi->id,
            'program_tes_id' => $programTesId,
        ]);

        return $this->layananAturan->snapshotProgram($programTesId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function barisHasilPenilaian(string $sesiId, HasilPenilaianSesi $hasil): array
    {
        $rows = [];

        foreach ($hasil->skorSection as $skor) {
            $rows[] = [
                'id' => (string) Str::uuid(),
                'sesi_ujian_id' => $sesiId,
                'section_id' => $skor->sectionId,
                'skema_penilaian_id' => $hasil->skemaPenilaianId,
                'jumlah_benar' => $skor->jumlahBenar,
                'jumlah_soal' => $skor->jumlahSoal,
                'skor_skala' => $skor->skorSkala,
                'rincian' => json_encode($skor->rincian),
            ];
        }

        return $rows;
    }
}
