<?php

namespace App\Http\Requests\Ujian;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterUjianRequest extends FormRequest
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
            'program_tes_id' => [
                'nullable',
                'uuid',
                Rule::exists('program_tes', 'id')->where(fn ($query) => $query->where('aktif', true)),
            ],
        ];
    }
}
