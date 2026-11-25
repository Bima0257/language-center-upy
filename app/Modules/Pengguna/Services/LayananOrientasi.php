<?php

namespace App\Modules\Pengguna\Services;

use App\Enums\JenisPeserta;
use App\Models\Pengguna;
use App\Modules\DataInduk\Repositories\Contracts\FakultasRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\ProgramStudiRepositoryInterface;
use App\Modules\Pengguna\Repositories\Contracts\PenggunaRepositoryInterface;
use App\Services\ImageCompressionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class LayananOrientasi
{
    public function __construct(
        private PenggunaRepositoryInterface $penggunaRepo,
        private FakultasRepositoryInterface $fakultasRepo,
        private ProgramStudiRepositoryInterface $programStudiRepo,
        private ImageCompressionService $imageCompression,
    ) {}

    public function dataVerifikasiIdentitas(Pengguna $pengguna): array
    {
        $profil = $pengguna->profilPeserta;
        $jenisPeserta = $profil !== null ? $profil->jenis_peserta : JenisPeserta::MAHASISWA;

        return [
            'pengguna' => $pengguna,
            'fakultas' => $this->fakultasRepo->semuaAktifBerurutan(),
            'programStudi' => $this->programStudiRepo->semuaAktifBerurutan(),
            'jenisPeserta' => $jenisPeserta->value,
            'opsiJenisPeserta' => collect(JenisPeserta::cases())
                ->map(fn (JenisPeserta $jenis): array => [
                    'value' => $jenis->value,
                    'label' => $jenis->label(),
                    'label_identitas' => $jenis->labelIdentitas(),
                ])
                ->all(),
            'bisaPilihJenis' => $profil === null,
            'prodiTerkunci' => $pengguna->sudahMemakaiUjian(),
            'sudahUnggah' => $pengguna->profilTerverifikasi(),
            'profil' => $profil ? [
                'jenis_peserta' => $profil->jenis_peserta->value,
                'nomor_identitas' => $profil->nomor_identitas,
                'institusi' => $profil->institusi,
                'fakultas_id' => $profil->fakultas_id,
                'program_studi_id' => $profil->program_studi_id,
                'tahun_mulai' => $profil->tahun_mulai,
            ] : null,
        ];
    }

    public function unggahIdentitas(
        Pengguna $pengguna,
        array $data,
        ?UploadedFile $fotoIdentitas,
        ?UploadedFile $foto,
    ): void {
        $this->pastikanProdiTidakBerubahSaatTerkunci($pengguna, $data);

        $profil = $pengguna->profilPeserta;
        $jenisPeserta = $profil !== null
            ? $profil->jenis_peserta
            : (JenisPeserta::tryFrom((string) ($data['jenis_peserta'] ?? '')) ?? JenisPeserta::MAHASISWA);
        $memerlukanAkademik = in_array($jenisPeserta, [JenisPeserta::MAHASISWA, JenisPeserta::DOSEN], true);

        $dataProfil = [
            'nomor_identitas' => $data['nomor_identitas'],
            'institusi' => filled($data['institusi'] ?? null) ? $data['institusi'] : $profil?->institusi,
            'fakultas_id' => $memerlukanAkademik ? ($data['fakultas_id'] ?? null) : null,
            'program_studi_id' => $memerlukanAkademik ? ($data['program_studi_id'] ?? null) : null,
            'tahun_mulai' => $data['tahun_mulai'] ?? $profil?->tahun_mulai,
        ];

        if ($profil === null) {
            $dataProfil['jenis_peserta'] = $jenisPeserta->value;
        }

        if ($fotoIdentitas !== null) {
            if ($profil !== null && $profil->foto_identitas) {
                Storage::disk('public')->delete($profil->foto_identitas);
            }

            $dataProfil['foto_identitas'] = $this->imageCompression->storeCompressed($fotoIdentitas, 'identities');
        }

        $this->penggunaRepo->perbaruiProfilPeserta($pengguna, $dataProfil);

        if ($foto !== null) {
            if ($pengguna->foto) {
                Storage::disk('public')->delete($pengguna->foto);
            }

            $this->penggunaRepo->update($pengguna, ['foto' => $this->imageCompression->storeCompressed($foto, 'photos')]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function pastikanProdiTidakBerubahSaatTerkunci(Pengguna $pengguna, array $data): void
    {
        if (! $pengguna->sudahMemakaiUjian()) {
            return;
        }

        $profil = $pengguna->loadMissing('profilPeserta')->profilPeserta;

        if ($profil === null) {
            return;
        }

        $prodiBaru = array_key_exists('program_studi_id', $data)
            ? $data['program_studi_id']
            : $profil->program_studi_id;
        $fakultasBaru = array_key_exists('fakultas_id', $data)
            ? $data['fakultas_id']
            : $profil->fakultas_id;

        if ($prodiBaru !== $profil->program_studi_id || $fakultasBaru !== $profil->fakultas_id) {
            throw new \RuntimeException('Program studi tidak dapat diubah setelah Anda mengikuti ujian. Hubungi admin.');
        }
    }
}
