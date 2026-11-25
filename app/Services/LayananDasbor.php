<?php

namespace App\Services;

use App\Models\Pengguna;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiUjianRepositoryInterface;
use App\Modules\Pengguna\Repositories\Contracts\PenggunaRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\UjianRepositoryInterface;

class LayananDasbor
{
    public function __construct(
        private SesiUjianRepositoryInterface $sesiRepo,
        private UjianRepositoryInterface $ujianRepo,
        private PenggunaRepositoryInterface $penggunaRepo,
    ) {}

    public function dataUntuk(Pengguna $pengguna): array
    {
        $data = [];

        if ($pengguna->hasRole('peserta')) {
            $programStudiId = $pengguna->loadMissing('profilPeserta')->profilPeserta?->program_studi_id;
            $data['sesiTerbaru'] = $this->sesiRepo->terbaruUntukPengguna($pengguna->id);
            $data['jumlahUjianTersedia'] = $this->ujianRepo->semuaTryoutTersedia($programStudiId)->count();
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
