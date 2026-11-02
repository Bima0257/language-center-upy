<?php

namespace App\Http\Requests\PendaftaranUjian;

use Illuminate\Foundation\Http\FormRequest;

class AjukanPendaftaranUjianRequest extends FormRequest
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
            'nim' => ['required', 'string', 'max:20'],
            'fakultas_id' => ['required', 'uuid', 'exists:fakultas,id'],
            'program_studi_id' => ['required', 'uuid', 'exists:program_studi,id'],
            'tahun_angkatan' => ['required', 'integer', 'min:2000', 'max:'.(date('Y') + 1)],
            'bukti_pembayaran' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];
    }
}
