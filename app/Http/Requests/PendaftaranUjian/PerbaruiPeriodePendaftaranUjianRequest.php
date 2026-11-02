<?php

namespace App\Http\Requests\PendaftaranUjian;

use Illuminate\Foundation\Http\FormRequest;

class PerbaruiPeriodePendaftaranUjianRequest extends FormRequest
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
            'nama' => ['required', 'string', 'max:255'],
            'dibuka_pada' => ['required', 'date'],
            'ditutup_pada' => ['required', 'date', 'after:dibuka_pada'],
            'aktif' => ['required', 'boolean'],
            'catatan' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
