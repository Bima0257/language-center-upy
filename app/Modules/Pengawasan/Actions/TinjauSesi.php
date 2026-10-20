<?php

namespace App\Modules\Pengawasan\Actions;

use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiUjianRepositoryInterface;
use App\Modules\Pengawasan\DTOs\DataKeputusanTinjauan;

class TinjauSesi
{
    public function __construct(
        private SesiUjianRepositoryInterface $sesiRepo,
    ) {}

    public function execute(string $sesiId, DataKeputusanTinjauan $keputusan): void
    {
        $this->sesiRepo->update($sesiId, [
            'status_tinjauan' => $keputusan->status,
            'catatan_tinjauan' => $keputusan->catatan,
            'peninjau_id' => $keputusan->peninjauId,
            'ditinjau_pada' => now(),
        ]);

        activity()
            ->performedOn($this->sesiRepo->findOrFail($sesiId))
            ->withProperties(['keputusan' => $keputusan->status])
            ->log('review:'.$keputusan->status);
    }
}
