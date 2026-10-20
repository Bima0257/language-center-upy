<?php

namespace App\Modules\Pengguna\Services;

use App\Models\Pengguna;
use App\Modules\DataInduk\Repositories\Contracts\FakultasRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\ProgramStudiRepositoryInterface;
use App\Modules\Pengguna\Repositories\Contracts\PenggunaRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class LayananOrientasi
{
    public function __construct(
        private PenggunaRepositoryInterface $penggunaRepo,
        private FakultasRepositoryInterface $fakultasRepo,
        private ProgramStudiRepositoryInterface $programStudiRepo,
    ) {}

    public function dataVerifikasiIdentitas(Pengguna $pengguna): array
    {
        $profil = $pengguna->profilMahasiswa;

        return [
            'pengguna' => $pengguna,
            'fakultas' => $this->fakultasRepo->semuaAktifBerurutan(),
            'programStudi' => $this->programStudiRepo->semuaAktifBerurutan(),
            'sudahUnggah' => $profil !== null && $profil->foto_identitas !== null && $profil->nim,
            'profil' => $profil ? [
                'nim' => $profil->nim,
                'fakultas_id' => $profil->fakultas_id,
                'program_studi_id' => $profil->program_studi_id,
                'tahun_angkatan' => $profil->tahun_angkatan,
            ] : null,
        ];
    }

    public function unggahIdentitas(
        Pengguna $pengguna,
        array $data,
        ?UploadedFile $fotoIdentitas,
        ?UploadedFile $foto,
    ): void {
        $profil = $pengguna->profilMahasiswa;

        $dataProfil = [
            'nim' => $data['nim'],
            'fakultas_id' => $data['fakultas_id'],
            'program_studi_id' => $data['program_studi_id'],
            'tahun_angkatan' => $data['tahun_angkatan'],
        ];

        if ($fotoIdentitas !== null) {
            if ($profil !== null && $profil->foto_identitas) {
                Storage::disk('public')->delete($profil->foto_identitas);
            }

            $dataProfil['foto_identitas'] = $fotoIdentitas->store('identities', 'public');
        }

        $this->penggunaRepo->perbaruiProfilMahasiswa($pengguna, $dataProfil);

        if ($foto !== null) {
            if ($pengguna->foto) {
                Storage::disk('public')->delete($pengguna->foto);
            }

            $this->penggunaRepo->update($pengguna, ['foto' => $foto->store('photos', 'public')]);
        }
    }
}
