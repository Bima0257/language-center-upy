<?php

namespace App\Http\Requests\Penjadwalan;

use Illuminate\Foundation\Http\FormRequest;

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
            'jenis_tes_id' => ['nullable', 'uuid', 'exists:jenis_tes,id'],
            'ujian_id' => ['nullable', 'uuid', 'exists:ujian,id'],
        ];
    }
}
