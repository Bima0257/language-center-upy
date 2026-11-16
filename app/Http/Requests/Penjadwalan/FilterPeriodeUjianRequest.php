<?php

namespace App\Http\Requests\Penjadwalan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterPeriodeUjianRequest extends FormRequest
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
            'per_page' => ['nullable', 'integer', Rule::in([15, 25, 50, 100])],
            'sort' => ['nullable', Rule::in(['judul', 'tanggal_mulai', 'tanggal_selesai', 'slot_ujian_count', 'aktif'])],
            'dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'f_program_tes_id' => ['nullable', 'uuid', 'exists:program_tes,id'],
            'f_ujian_id' => ['nullable', 'uuid', 'exists:ujian,id'],
            'f_aktif' => ['nullable', 'boolean'],
            'program_tes_id' => ['nullable', 'uuid', 'exists:program_tes,id'],
            'ujian_id' => ['nullable', 'uuid', 'exists:ujian,id'],
            'aktif' => ['nullable', 'boolean'],
        ];
    }
}
