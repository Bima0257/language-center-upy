<?php

namespace App\Http\Requests\Pengguna;

use App\Enums\JenisPeserta;
use App\Models\Pengguna;
use App\Models\ProfilPeserta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnggahIdentitasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $jenisPeserta = $this->jenisPeserta();
        $profil = $this->profilSaatIni();
        $memerlukanAkademik = in_array($jenisPeserta, [JenisPeserta::MAHASISWA, JenisPeserta::DOSEN], true);
        $memerlukanTahun = $jenisPeserta === JenisPeserta::MAHASISWA;

        return [
            'jenis_peserta' => [
                $profil === null ? 'required' : 'nullable',
                Rule::enum(JenisPeserta::class),
            ],
            'nomor_identitas' => [
                'required',
                'string',
                'max:30',
                Rule::unique('profil_peserta', 'nomor_identitas')->ignore($profil?->id),
            ],
            'institusi' => [
                Rule::requiredIf($jenisPeserta === JenisPeserta::UMUM),
                'nullable',
                'string',
                'max:150',
            ],
            'fakultas_id' => [
                Rule::requiredIf($memerlukanAkademik),
                'nullable',
                'uuid',
                'exists:fakultas,id',
            ],
            'program_studi_id' => [
                Rule::requiredIf($memerlukanAkademik),
                'nullable',
                'uuid',
                'exists:program_studi,id',
            ],
            'tahun_mulai' => [
                Rule::requiredIf($memerlukanTahun),
                'nullable',
                'integer',
                'min:2000',
                'max:'.(date('Y') + 1),
            ],
            'foto_identitas' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.config('image.max_kb'),
                'dimensions:max_width=12000,max_height=12000',
            ],
            'foto' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.config('image.max_kb'),
                'dimensions:max_width=12000,max_height=12000',
            ],
        ];
    }

    private function profilSaatIni(): ?ProfilPeserta
    {
        /** @var Pengguna|null $pengguna */
        $pengguna = $this->user();

        return $pengguna?->profilPeserta;
    }

    private function jenisPeserta(): JenisPeserta
    {
        $profil = $this->profilSaatIni();

        if ($profil !== null) {
            return $profil->jenis_peserta;
        }

        return JenisPeserta::tryFrom((string) $this->input('jenis_peserta')) ?? JenisPeserta::MAHASISWA;
    }
}
