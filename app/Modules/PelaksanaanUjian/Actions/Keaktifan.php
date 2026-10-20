<?php

namespace App\Modules\PelaksanaanUjian\Actions;

use App\Enums\StatusSesi;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiUjianRepositoryInterface;

class Keaktifan
{
    public function __construct(
        private SesiUjianRepositoryInterface $sesiRepo,
    ) {}

    public function execute(string $sesiId): void
    {
        $sesi = $this->sesiRepo->findOrFail($sesiId);

        if ($sesi->status !== StatusSesi::IN_PROGRESS) {
            return;
        }

        $this->sesiRepo->update($sesiId, [
            'keaktifan_terakhir_pada' => now(),
        ]);

        if ($sesi->dimulai_pada) {
            $elapsedMinutes = now()->diffInMinutes($sesi->dimulai_pada);
            $ujian = $sesi->slotUjian?->periodeUjian?->ujian;
            $durationMinutes = $ujian !== null ? $ujian->durasi_menit : 160;

            if ($elapsedMinutes > $durationMinutes + 1) {
                $this->sesiRepo->update($sesiId, [
                    'status' => StatusSesi::TERMINATED,
                    'dihentikan_pada' => now(),
                    'alasan_penghentian' => 'Waktu habis (timeout detected by heartbeat).',
                ]);
            }
        }
    }
}
