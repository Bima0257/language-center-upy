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
            'search' => ['nullable', 'string', 'max:100'],
            'jenis_tes_id' => ['nullable', 'uuid', 'exists:jenis_tes,id'],
            'ujian_id' => ['nullable', 'uuid', 'exists:ujian,id'],
            'periode_ujian_id' => ['nullable', 'uuid', 'exists:periode_ujian,id'],
            'slot_ujian_id' => ['nullable', 'uuid', 'exists:slot_ujian,id'],
            'status' => ['nullable', Rule::enum(StatusPendaftaranUjian::class)],
        ];
    }
}
