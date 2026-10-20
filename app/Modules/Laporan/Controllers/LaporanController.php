<?php

namespace App\Modules\Laporan\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SesiUjian;
use App\Modules\Laporan\Repositories\Contracts\LaporanRepositoryInterface;
use Inertia\Inertia;
use Inertia\Response;

class LaporanController extends Controller
{
    public function __construct(
        private LaporanRepositoryInterface $laporanRepo,
    ) {}

    public function integritas(): Response
    {
        return Inertia::render('Admin/Laporan/LaporanSesi', [
            'sesiUjian' => $this->laporanRepo->semuaIntegritas(),
        ]);
    }

    public function eksporIntegritas(SesiUjian $sesiUjian): Response
    {
        return Inertia::render('Admin/Laporan/EksporIntegritas', [
            'sesiUjian' => $this->laporanRepo->temukanUntukEkspor($sesiUjian->id),
        ]);
    }
}
