<?php

namespace App\Http\Requests\Ujian;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanUjianRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'jenis_tes_id' => [
                'required',
                'uuid',
                Rule::exists('jenis_tes', 'id')->where(fn ($query) => $query->where('aktif', true)),
            ],
            'bank_soal_id' => [
                'required',
                'uuid',
                Rule::exists('bank_soal', 'id')->where(fn ($query) => $query
                    ->where('aktif', true)
                    ->where('jenis_tes_id', $this->input('jenis_tes_id'))),
            ],
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'mode' => ['required', 'in:tryout,official'],
            'durasi_menit' => ['required', 'integer', 'min:1', 'max:480'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
