<?php

namespace App\Modules\Laporan\Repositories;

use App\Models\SesiUjian;
use App\Modules\Laporan\Repositories\Contracts\LaporanRepositoryInterface;
use Illuminate\Support\Collection;

class LaporanRepository implements LaporanRepositoryInterface
{
    public function semuaIntegritas(): Collection
    {
        return SesiUjian::with([
            'pengguna',
            'slotUjian.periodeUjian.ujian',
        ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function temukanUntukEkspor(string $id): ?SesiUjian
    {
        return SesiUjian::with([
            'pengguna', 'catatanPelanggaran', 'slotUjian.periodeUjian.ujian', 'slotUjian', 'jawaban.soal',
        ])->find($id);
    }
}
