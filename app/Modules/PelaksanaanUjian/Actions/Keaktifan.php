<?php

namespace App\Modules\PelaksanaanUjian\Actions;

use App\Enums\StatusSesi;
use App\Modules\PelaksanaanUjian\DTOs\HasilWaktuUjian;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiUjianRepositoryInterface;
use App\Modules\PelaksanaanUjian\Services\LayananWaktuUjian;

class Keaktifan
{
    public function __construct(
        private SesiUjianRepositoryInterface $sesiRepo,
        private LayananWaktuUjian $layananWaktu,
    ) {}

    public function execute(string $sesiId): HasilWaktuUjian
    {
        $sesi = $this->sesiRepo->findOrFail($sesiId);

        if ($sesi->status === StatusSesi::IN_PROGRESS) {
            $this->sesiRepo->update($sesiId, [
                'keaktifan_terakhir_pada' => now(),
            ]);
        }

        return $this->layananWaktu->proses($sesi);
    }
}
