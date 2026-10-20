<?php

namespace App\Http\Requests\DataInduk;

use Illuminate\Foundation\Http\FormRequest;

class SimpanInterpretasiSkorRequest extends FormRequest
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
            'jenis_tes_id' => ['required', 'uuid', 'exists:jenis_tes,id'],
            'skor_minimum' => ['required', 'integer', 'min:0'],
            'skor_maksimum' => ['required', 'integer', 'gte:skor_minimum'],
            'tingkat_cefr' => ['required', 'string', 'max:10'],
            'label_tingkat' => ['required', 'string', 'max:50'],
            'lulus' => ['boolean'],
            'deskripsi' => ['nullable', 'string'],
        ];
    }
}
