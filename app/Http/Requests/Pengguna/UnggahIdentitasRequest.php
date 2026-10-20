<?php

namespace App\Http\Requests\Pengguna;

use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'nim' => 'required|string|max:20',
            'fakultas_id' => 'required|uuid|exists:fakultas,id',
            'program_studi_id' => 'required|uuid|exists:program_studi,id',
            'tahun_angkatan' => 'required|integer|min:2000|max:'.(date('Y') + 1),
            'foto_identitas' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'foto' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ];
    }
}
