<?php

namespace App\Modules\PelaksanaanUjian\Actions;

use App\Enums\AlasanSelesaiPart;
use App\Enums\StatusSesi;
use App\Modules\PelaksanaanUjian\DTOs\HasilPenyerahan;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiPartUjianRepositoryInterface;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiUjianRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

class AkhiriSesiKarenaWaktu
{
    public function __construct(
        private SesiUjianRepositoryInterface $sesiRepo,
        private SesiPartUjianRepositoryInterface $sesiPartRepo,
        private SerahkanUjian $serahkanUjian,
    ) {}

    public function execute(
        string $sesiId,
        AlasanSelesaiPart $alasan = AlasanSelesaiPart::WAKTU_HABIS,
    ): ?HasilPenyerahan {
        $sesi = $this->sesiRepo->findOrFail($sesiId);

        if ($sesi->status !== StatusSesi::IN_PROGRESS) {
            return null;
        }

        try {
            return $this->serahkanUjian->execute($sesiId, $alasan);
        } catch (Throwable $exception) {
            $sesiTerkini = $this->sesiRepo->findOrFail($sesiId);

            if ($sesiTerkini->status !== StatusSesi::IN_PROGRESS) {
                return null;
            }

            Log::error('Gagal mengakhiri sesi ujian otomatis.', [
                'sesi_ujian_id' => $sesiId,
                'alasan' => $alasan->value,
                'error' => $exception->getMessage(),
            ]);

            $this->sesiPartRepo->selesaikanSemuaTerbuka(
                $sesiId,
                AlasanSelesaiPart::DIBATALKAN,
                now(),
            );

            $this->sesiRepo->update($sesiId, [
                'status' => StatusSesi::TERMINATED,
                'dihentikan_pada' => now(),
                'alasan_penghentian' => 'Sesi dihentikan otomatis karena penilaian gagal diproses.',
            ]);

            return null;
        }
    }
}
