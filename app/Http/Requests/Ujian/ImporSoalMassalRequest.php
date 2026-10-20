<?php

namespace App\Http\Requests\Ujian;

use Illuminate\Foundation\Http\FormRequest;

class ImporSoalMassalRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'soal' => ['required', 'array', 'min:1', 'max:500'],
            'soal.*.bank_soal_id' => ['required', 'uuid', 'exists:bank_soal,id'],
            'soal.*.keterampilan_id' => ['required', 'uuid', 'exists:keterampilan,id'],
            'soal.*.bagian_soal_id' => ['nullable', 'uuid', 'exists:bagian_soal,id'],
            'soal.*.materi_soal_id' => ['nullable', 'uuid', 'exists:materi_soal,id'],
            'soal.*.teks_soal' => ['nullable', 'string'],
            'soal.*.pilihan_a' => ['nullable', 'string'],
            'soal.*.pilihan_b' => ['nullable', 'string'],
            'soal.*.pilihan_c' => ['nullable', 'string'],
            'soal.*.pilihan_d' => ['nullable', 'string'],
            'soal.*.kunci_jawaban' => ['required', 'string', 'max:1', 'in:A,B,C,D'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
