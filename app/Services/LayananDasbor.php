<?php

namespace App\Services;

use App\Models\Pengguna;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiUjianRepositoryInterface;
use App\Modules\Pengguna\Repositories\Contracts\PenggunaRepositoryInterface;
use App\Modules\Penjadwalan\Repositories\Contracts\SlotUjianRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\UjianRepositoryInterface;

class LayananDasbor
{
    public function __construct(
        private SesiUjianRepositoryInterface $sesiRepo,
        private SlotUjianRepositoryInterface $slotRepo,
        private UjianRepositoryInterface $ujianRepo,
        private PenggunaRepositoryInterface $penggunaRepo,
    ) {}

    public function dataUntuk(Pengguna $pengguna): array
    {
        $data = [];

        if ($pengguna->hasRole('student')) {
            $data['sesiTerbaru'] = $this->sesiRepo->terbaruUntukPengguna($pengguna->id);
            $data['jumlahUjianTersedia'] = $this->slotRepo->slotTersedia()->count();
        }

        if ($pengguna->hasAnyRole(['admin', 'superadmin'])) {
            $data['jumlahPengguna'] = $this->penggunaRepo->count();
            $data['jumlahUjian'] = $this->ujianRepo->count();
            $data['jumlahSesiAktif'] = $this->sesiRepo->hitungAktif();
            $data['jumlahSesiDitandai'] = $this->sesiRepo->hitungDitandaiMenungguTinjauan();
        }

        return $data;
    }
}
