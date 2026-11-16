<?php

namespace App\Http\Requests\Pengguna;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterPenggunaRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in([20, 50, 100])],
            'sort' => ['nullable', Rule::in(['name', 'email', 'created_at', 'aktif', 'sesi_ujian_count'])],
            'dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'f_role' => ['nullable', Rule::in(['admin', 'peserta', 'custom'])],
            'role' => ['nullable', Rule::in(['admin', 'peserta', 'custom'])],
            'f_aktif' => ['nullable', 'boolean'],
            'aktif' => ['nullable', 'boolean'],
        ];
    }
}
