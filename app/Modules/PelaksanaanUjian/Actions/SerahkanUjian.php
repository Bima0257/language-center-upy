<?php

namespace App\Modules\PelaksanaanUjian\Actions;

use App\Enums\StatusSesi;
use App\Modules\PelaksanaanUjian\DTOs\HasilPenyerahan;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiUjianRepositoryInterface;
use App\Modules\Penilaian\Actions\HitungSkorTotal;
use App\Modules\Penilaian\Actions\NilaiOtomatisMembaca;
use App\Modules\Penilaian\Actions\NilaiOtomatisMenyimak;
use Illuminate\Support\Facades\DB;

class SerahkanUjian
{
    public function __construct(
        private SesiUjianRepositoryInterface $sesiRepo,
        private NilaiOtomatisMembaca $nilaiOtomatisMembaca,
        private NilaiOtomatisMenyimak $nilaiOtomatisMenyimak,
        private HitungSkorTotal $hitungSkorTotal,
    ) {}

    public function execute(string $sesiId): HasilPenyerahan
    {
        $sesi = $this->sesiRepo->temukanDenganJawaban($sesiId);

        if (! $sesi || $sesi->status === StatusSesi::SUBMITTED) {
            throw new \RuntimeException('Ujian sudah disubmit sebelumnya.');
        }

        [$skorMembaca, $skorMenyimak, $skorTotal] = DB::transaction(function () use ($sesi, $sesiId) {
            $skorMembaca = $this->nilaiOtomatisMembaca->execute($sesiId);
            $skorMenyimak = $this->nilaiOtomatisMenyimak->execute($sesiId);

            $skorTotal = $this->hitungSkorTotal->execute(
                $skorMembaca,
                $skorMenyimak,
                0,
                0,
            );

            $updates = [
                'status' => StatusSesi::SUBMITTED,
                'diserahkan_pada' => now(),
                'skor_membaca' => $skorMembaca,
                'skor_menyimak' => $skorMenyimak,
                'skor_total' => $skorTotal,
            ];

            $batasPelanggaran = $sesi->batasPelanggaranMaksimum();

            if ($sesi->jumlah_pelanggaran >= $batasPelanggaran) {
                $updates['ditandai'] = true;
                $updates['alasan_penandaan'] = "Pelanggaran mencapai {$batasPelanggaran} strike.";
            }

            $this->sesiRepo->update($sesiId, $updates);

            return [$skorMembaca, $skorMenyimak, $skorTotal];
        });

        activity()
            ->performedOn($sesi)
            ->withProperties([
                'skor_membaca' => $skorMembaca,
                'skor_menyimak' => $skorMenyimak,
                'skor_total' => $skorTotal,
            ])
            ->log('exam_submitted');

        return new HasilPenyerahan(
            sesiId: $sesiId,
            skorMembaca: $skorMembaca,
            skorMenyimak: $skorMenyimak,
            skorTotal: $skorTotal,
        );
    }
}
