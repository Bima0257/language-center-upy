<?php

namespace App\Http\Requests\Ujian;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanBankSoalRequest extends FormRequest
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
            'jenis_tes_id' => [
                'required',
                'uuid',
                Rule::exists('jenis_tes', 'id')->where(fn ($query) => $query->where('aktif', true)),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'aktif' => ['boolean'],
        ];
    }
}
