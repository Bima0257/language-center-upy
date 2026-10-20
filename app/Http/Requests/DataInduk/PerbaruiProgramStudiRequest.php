<?php

namespace App\Http\Requests\DataInduk;

use Illuminate\Foundation\Http\FormRequest;

class PerbaruiProgramStudiRequest extends FormRequest
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
            'fakultas_id' => ['required', 'uuid', 'exists:fakultas,id'],
            'nama' => ['required', 'string', 'max:100'],
            'kode' => ['required', 'string', 'max:20'],
            'aktif' => ['boolean'],
        ];
    }
}
