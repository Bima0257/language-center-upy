<?php

namespace App\Modules\Keamanan\Repositories\Contracts;

use App\Models\CatatanPelanggaran;
use Illuminate\Support\Collection;

interface PelanggaranRepositoryInterface
{
    public function create(array $data): CatatanPelanggaran;

    public function hitungBerdasarkanSesi(string $sesiId): int;

    public function ambilBerdasarkanSesi(string $sesiId): Collection;
}
