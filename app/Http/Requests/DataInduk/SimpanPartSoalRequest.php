<?php

namespace App\Http\Requests\DataInduk;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanPartSoalRequest extends FormRequest
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
            'bank_soal_konteks_id' => ['nullable', 'uuid', 'exists:bank_soal,id'],
            'bank_soal_id' => ['required', 'uuid', 'exists:bank_soal,id'],
            'section_id' => ['required', 'uuid', 'exists:section,id'],
            'nama' => [
                'required',
                'string',
                'max:100',
                Rule::unique('part_soal', 'nama')->where(function ($query) {
                    return $query
                        ->where('bank_soal_id', $this->input('bank_soal_id'))
                        ->where('section_id', $this->input('section_id'));
                }),
            ],
            'petunjuk' => ['nullable', 'string'],
            'aktif' => ['boolean'],
        ];
    }
}
