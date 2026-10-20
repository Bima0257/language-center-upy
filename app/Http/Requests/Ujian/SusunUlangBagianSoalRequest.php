<?php

namespace App\Http\Requests\Ujian;

use Illuminate\Foundation\Http\FormRequest;

class SusunUlangBagianSoalRequest extends FormRequest
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
            'urutan.*.bagian_soal_id' => ['required', 'uuid', 'distinct'],
            'urutan.*.urutan' => ['required', 'integer', 'min:1'],
        ];
    }
}
