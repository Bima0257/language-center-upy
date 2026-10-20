<?php

namespace App\Http\Requests\DataInduk;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanProgramStudiMassalRequest extends FormRequest
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
            'program_studi' => ['required', 'array', 'min:1', 'max:10'],
            'program_studi.*.nama' => [
                'required',
                'string',
                'max:100',
                'distinct',
                Rule::unique('program_studi', 'nama'),
            ],
            'program_studi.*.kode' => [
                'required',
                'string',
                'max:20',
                'distinct',
                Rule::unique('program_studi', 'kode'),
            ],
            'program_studi.*.aktif' => ['boolean'],
        ];
    }
}
