<?php

namespace App\Modules\Keamanan\Actions;

use App\Enums\StatusSesi;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiUjianRepositoryInterface;

class HentikanSesi
{
    public function __construct(
        private SesiUjianRepositoryInterface $sesiRepo,
    ) {}

    public function execute(string $sesiId, string $alasan): void
    {
        $sesi = $this->sesiRepo->findOrFail($sesiId);

        $this->sesiRepo->update($sesiId, [
            'status' => StatusSesi::TERMINATED,
            'dihentikan_pada' => now(),
            'alasan_penghentian' => $alasan,
            'ditandai' => true,
            'alasan_penandaan' => $alasan,
        ]);

        activity()
            ->performedOn($sesi)
            ->withProperties(['alasan' => $alasan])
            ->log('session_terminated');
    }
}
