<?php

namespace App\Http\Requests\Ujian;

use Illuminate\Foundation\Http\FormRequest;

class SimpanSusunanRequest extends FormRequest
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
            'urutan_bagian_soal' => ['sometimes', 'array'],
            'urutan_bagian_soal.*.bagian_soal_id' => ['required', 'uuid', 'distinct'],
            'urutan_bagian_soal.*.urutan' => ['required', 'integer', 'min:1'],
            'urutan_soal' => ['sometimes', 'array'],
            'urutan_soal.*.soal_id' => ['required', 'uuid', 'distinct'],
            'urutan_soal.*.nomor' => ['required', 'integer', 'min:1'],
        ];
    }
}
