<?php

namespace App\Modules\Pengguna\Services;

use App\Models\Pengguna;
use App\Modules\Pengguna\Repositories\Contracts\PenggunaRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class LayananVerifikasiPengguna
{
    public function __construct(
        private PenggunaRepositoryInterface $penggunaRepo,
    ) {}

    public function dataDaftar(): array
    {
        return [
            'pengguna' => $this->penggunaRepo->penggunaMenungguVerifikasi(),
        ];
    }

    public function setujui(Pengguna $pengguna): void
    {
        $this->pastikanProfil($pengguna);

        $this->penggunaRepo->perbaruiProfilMahasiswa($pengguna, [
            'terverifikasi' => true,
            'diverifikasi_pada' => now(),
            'verifikator_id' => auth()->id(),
        ]);
    }

    public function batalkanVerifikasi(Pengguna $pengguna): void
    {
        $this->pastikanProfil($pengguna);

        $this->penggunaRepo->perbaruiProfilMahasiswa($pengguna, [
            'terverifikasi' => false,
            'diverifikasi_pada' => null,
            'verifikator_id' => null,
        ]);
    }

    public function tolak(Pengguna $pengguna, ?string $alasan): void
    {
        $profil = $pengguna->profilMahasiswa;

        if ($profil !== null) {
            if ($profil->foto_identitas) {
                Storage::disk('public')->delete($profil->foto_identitas);
            }

            $this->penggunaRepo->perbaruiProfilMahasiswa($pengguna, [
                'foto_identitas' => null,
                'catatan_verifikasi' => $alasan,
            ]);
        }

        if ($pengguna->foto) {
            Storage::disk('public')->delete($pengguna->foto);
        }

        $this->penggunaRepo->update($pengguna, ['foto' => null]);
    }

    private function pastikanProfil(Pengguna $pengguna): void
    {
        if ($pengguna->profilMahasiswa === null) {
            throw new RuntimeException('Profile mahasiswa tidak ditemukan.');
        }
    }
}
