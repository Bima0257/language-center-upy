<?php

namespace App\Modules\PelaksanaanUjian\Actions;

use App\Models\SesiUjian;
use App\Modules\PelaksanaanUjian\DTOs\HasilWaktuUjian;
use App\Modules\PelaksanaanUjian\Services\LayananWaktuUjian;

class SelesaikanPart
{
    public function __construct(
        private LayananWaktuUjian $layananWaktu,
    ) {}

    public function execute(SesiUjian $sesi): HasilWaktuUjian
    {
        return $this->layananWaktu->proses($sesi);
    }
}
