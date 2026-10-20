<?php

namespace App\Modules\Ujian\Repositories\Contracts;

use App\Models\KebijakanMateriSoal;
use Illuminate\Database\Eloquent\Collection;

interface KebijakanMateriSoalRepositoryInterface
{
    public function temukanDenganBankDanKeterampilan(string $bankSoalId, string $keterampilanId): ?KebijakanMateriSoal;

    /**
     * @return Collection<int, KebijakanMateriSoal>
     */
    public function all(): Collection;
}
