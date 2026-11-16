<?php

namespace App\Http\Requests\PendaftaranUjian;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterPeriodePendaftaranUjianRequest extends FormRequest
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
            'sort' => ['nullable', Rule::in(['nama', 'dibuka_pada', 'ditutup_pada', 'created_at', 'slot_ujian_count'])],
            'dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'f_status' => ['nullable', Rule::in(['upcoming', 'open', 'closed', 'inactive'])],
            'f_program_tes_id' => [
                'nullable',
                'uuid',
                Rule::exists('program_tes', 'id')->where(fn ($query) => $query->where('aktif', true)),
            ],
            'status' => ['nullable', Rule::in(['upcoming', 'open', 'closed', 'inactive'])],
            'program_tes_id' => [
                'nullable',
                'uuid',
                Rule::exists('program_tes', 'id')->where(fn ($query) => $query->where('aktif', true)),
            ],
        ];
    }
}
