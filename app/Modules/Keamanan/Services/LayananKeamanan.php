<?php

namespace App\Modules\Keamanan\Services;

use App\Models\SesiUjian;
use App\Modules\Keamanan\Repositories\Contracts\PelanggaranRepositoryInterface;

class LayananKeamanan
{
    public function __construct(
        private PelanggaranRepositoryInterface $pelanggaranRepo,
    ) {}

    public function catatPeristiwaSistem(string $sesiId, string $peristiwa): void
    {
        activity()
            ->performedOn(SesiUjian::findOrFail($sesiId))
            ->log($peristiwa);
    }

    public function ambilJumlahPelanggaran(string $sesiId): int
    {
        return $this->pelanggaranRepo->hitungBerdasarkanSesi($sesiId);
    }

    public function batasPelanggaranTercapai(string $sesiId): bool
    {
        return $this->ambilJumlahPelanggaran($sesiId) >= 3;
    }
}
