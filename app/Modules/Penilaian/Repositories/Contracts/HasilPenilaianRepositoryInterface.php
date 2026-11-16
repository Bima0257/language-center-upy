<?php

namespace App\Modules\Penilaian\Repositories\Contracts;

use App\Models\HasilPenilaian;
use Illuminate\Database\Eloquent\Collection;

interface HasilPenilaianRepositoryInterface
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function buatBanyak(array $rows): void;

    /**
     * @return Collection<int, HasilPenilaian>
     */
    public function untukSesi(string $sesiUjianId): Collection;
}
