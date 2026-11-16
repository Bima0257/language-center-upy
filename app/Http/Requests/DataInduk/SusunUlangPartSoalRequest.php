<?php

namespace App\Http\Requests\DataInduk;

use Illuminate\Foundation\Http\FormRequest;

class SusunUlangPartSoalRequest extends FormRequest
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
            'part_soal' => ['required', 'array', 'min:1'],
            'part_soal.*' => ['required', 'uuid', 'distinct'],
        ];
    }
}
