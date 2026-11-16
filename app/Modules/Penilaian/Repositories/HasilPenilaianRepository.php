<?php

namespace App\Modules\Penilaian\Repositories;

use App\Models\HasilPenilaian;
use App\Modules\Penilaian\Repositories\Contracts\HasilPenilaianRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class HasilPenilaianRepository implements HasilPenilaianRepositoryInterface
{
    public function buatBanyak(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $timestamp = Carbon::now();

        foreach ($rows as &$row) {
            $row['created_at'] = $timestamp;
            $row['updated_at'] = $timestamp;
        }

        unset($row);

        HasilPenilaian::insert($rows);
    }

    /**
     * @return Collection<int, HasilPenilaian>
     */
    public function untukSesi(string $sesiUjianId): Collection
    {
        return HasilPenilaian::with('section')
            ->where('sesi_ujian_id', $sesiUjianId)
            ->orderBy('created_at')
            ->get();
    }
}
