<?php

namespace App\Http\Requests\Ujian;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanUjianRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'program_tes_id' => [
                'required',
                'uuid',
                Rule::exists('program_tes', 'id')->where(fn ($query) => $query->where('aktif', true)),
            ],
            'bank_soal_id' => [
                'required',
                'uuid',
                Rule::exists('bank_soal', 'id')->where(fn ($query) => $query
                    ->where('aktif', true)
                    ->where('program_tes_id', $this->input('program_tes_id'))),
            ],
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'mode' => ['required', 'in:tryout,official'],
            'durasi_menit' => ['required', 'integer', 'min:1', 'max:480'],
            'lingkup' => ['required', 'in:umum,prodi'],
            'program_studi_ids' => [
                'exclude_unless:lingkup,prodi',
                'array',
                Rule::requiredIf(fn (): bool => $this->input('lingkup') === 'prodi'),
                'min:1',
            ],
            'program_studi_ids.*' => [
                'uuid',
                Rule::exists('program_studi', 'id')->where(fn ($query) => $query->where('aktif', true)),
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
