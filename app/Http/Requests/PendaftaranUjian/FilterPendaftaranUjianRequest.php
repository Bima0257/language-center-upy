<?php

namespace App\Http\Requests\PendaftaranUjian;

use App\Enums\StatusPendaftaranUjian;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterPendaftaranUjianRequest extends FormRequest
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
            'program_tes_id' => ['nullable', 'uuid', 'exists:program_tes,id'],
            'ujian_id' => ['nullable', 'uuid', 'exists:ujian,id'],
            'periode_ujian_id' => ['nullable', 'uuid', 'exists:periode_ujian,id'],
            'slot_ujian_id' => ['nullable', 'uuid', 'exists:slot_ujian,id'],
            'f_slot_ujian_id' => ['nullable', 'uuid', 'exists:slot_ujian,id'],
            'status' => ['nullable', Rule::enum(StatusPendaftaranUjian::class)],
            'f_status' => ['nullable', Rule::enum(StatusPendaftaranUjian::class)],
        ];
    }
}
