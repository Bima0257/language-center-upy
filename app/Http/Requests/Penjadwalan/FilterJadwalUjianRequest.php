<?php

namespace App\Http\Requests\Penjadwalan;

use Illuminate\Foundation\Http\FormRequest;

class FilterJadwalUjianRequest extends FormRequest
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
            'program_tes_id' => ['nullable', 'uuid', 'exists:program_tes,id'],
            'ujian_id' => ['nullable', 'uuid', 'exists:ujian,id'],
            'periode_ujian_id' => ['nullable', 'uuid', 'exists:periode_ujian,id'],
        ];
    }
}
