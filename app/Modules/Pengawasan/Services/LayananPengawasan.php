<?php

namespace App\Modules\Pengawasan\Services;

use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiUjianRepositoryInterface;
use Illuminate\Support\Collection;

class LayananPengawasan
{
    public function __construct(
        private SesiUjianRepositoryInterface $sesiRepo,
    ) {}

    public function ambilSesiAktif(): Collection
    {
        return $this->sesiRepo->ambilSesiAktif();
    }

    public function ambilSesiDitandai(bool $sertakanDitinjau = false): Collection
    {
        return $this->sesiRepo->ambilSesiDitandai($sertakanDitinjau);
    }
}
