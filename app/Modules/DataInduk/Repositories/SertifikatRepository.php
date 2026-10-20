<?php

namespace App\Modules\DataInduk\Repositories;

use App\Models\Sertifikat;
use App\Modules\DataInduk\Repositories\Contracts\SertifikatRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SertifikatRepository implements SertifikatRepositoryInterface
{
    public function semuaDenganRelasi(): Collection
    {
        return Sertifikat::with([
            'sesiUjian.pengguna',
            'sesiUjian.periodeUjian.ujian',
            'sesiUjian.slotUjian',
        ])->orderBy('diterbitkan_pada', 'desc')->get();
    }

    public function create(array $data): Sertifikat
    {
        return Sertifikat::create($data);
    }

    public function delete(Sertifikat $sertifikat): void
    {
        $sertifikat->delete();
    }
}
