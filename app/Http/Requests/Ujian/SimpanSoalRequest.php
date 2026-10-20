<?php

namespace App\Http\Requests\Ujian;

use Illuminate\Foundation\Http\FormRequest;

class SimpanSoalRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'bank_soal_id' => ['required', 'uuid', 'exists:bank_soal,id'],
            'keterampilan_id' => ['required', 'uuid', 'exists:keterampilan,id'],
            'bagian_soal_id' => ['required', 'uuid', 'exists:bagian_soal,id'],
            'materi_soal_id' => ['nullable', 'uuid', 'exists:materi_soal,id'],
            'teks_soal' => ['nullable', 'string'],
            'pilihan_a' => ['nullable', 'string'],
            'pilihan_b' => ['nullable', 'string'],
            'pilihan_c' => ['nullable', 'string'],
            'pilihan_d' => ['nullable', 'string'],
            'kunci_jawaban' => ['required', 'string', 'max:1', 'in:A,B,C,D'],
            'urutan' => ['required', 'integer', 'min:1'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
