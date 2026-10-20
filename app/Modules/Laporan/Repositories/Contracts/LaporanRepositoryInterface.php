<?php

namespace App\Modules\Laporan\Repositories\Contracts;

use App\Models\SesiUjian;
use Illuminate\Support\Collection;

interface LaporanRepositoryInterface
{
    public function semuaIntegritas(): Collection;

    public function temukanUntukEkspor(string $id): ?SesiUjian;
}
