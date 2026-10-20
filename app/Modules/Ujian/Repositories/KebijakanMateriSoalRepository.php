<?php

namespace App\Modules\Ujian\Repositories;

use App\Models\KebijakanMateriSoal;
use App\Modules\Ujian\Repositories\Contracts\KebijakanMateriSoalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class KebijakanMateriSoalRepository implements KebijakanMateriSoalRepositoryInterface
{
    public function temukanDenganBankDanKeterampilan(string $bankSoalId, string $keterampilanId): ?KebijakanMateriSoal
    {
        return KebijakanMateriSoal::with('aturanMedia')
            ->where('bank_soal_id', $bankSoalId)
            ->where('keterampilan_id', $keterampilanId)
            ->first();
    }

    public function all(): Collection
    {
        return KebijakanMateriSoal::with('aturanMedia')->orderBy('keterampilan_id')->get();
    }
}
