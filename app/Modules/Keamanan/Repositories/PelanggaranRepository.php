<?php

namespace App\Modules\Keamanan\Repositories;

use App\Models\CatatanPelanggaran;
use App\Modules\Keamanan\Repositories\Contracts\PelanggaranRepositoryInterface;
use Illuminate\Support\Collection;

class PelanggaranRepository implements PelanggaranRepositoryInterface
{
    public function create(array $data): CatatanPelanggaran
    {
        return CatatanPelanggaran::create($data);
    }

    public function hitungBerdasarkanSesi(string $sesiId): int
    {
        return CatatanPelanggaran::where('sesi_ujian_id', $sesiId)->count();
    }

    public function ambilBerdasarkanSesi(string $sesiId): Collection
    {
        return CatatanPelanggaran::where('sesi_ujian_id', $sesiId)
            ->orderBy('created_at')
            ->get();
    }
}
