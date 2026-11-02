<?php

namespace App\Http\Requests\PendaftaranUjian;

use Illuminate\Foundation\Http\FormRequest;

class SimpanPeriodePendaftaranUjianRequest extends FormRequest
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
            'periode_ujian_id' => ['required', 'uuid', 'exists:periode_ujian,id'],
            'nama' => ['required', 'string', 'max:255'],
            'dibuka_pada' => ['required', 'date'],
            'ditutup_pada' => ['required', 'date', 'after:dibuka_pada'],
            'aktif' => ['required', 'boolean'],
            'catatan' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
