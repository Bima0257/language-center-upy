<?php

namespace App\Http\Requests\Ujian;

use Illuminate\Foundation\Http\FormRequest;

class SusunUlangSoalRequest extends FormRequest
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
            'urutan' => ['required', 'array'],
            'urutan.*.soal_id' => ['required', 'uuid', 'distinct'],
            'urutan.*.nomor' => ['required', 'integer', 'min:1'],
        ];
    }
}
