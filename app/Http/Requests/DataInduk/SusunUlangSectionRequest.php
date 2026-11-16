<?php

namespace App\Http\Requests\DataInduk;

use Illuminate\Foundation\Http\FormRequest;

class SusunUlangSectionRequest extends FormRequest
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
            'section' => ['required', 'array', 'min:1'],
            'section.*' => ['required', 'uuid', 'distinct'],
            'program_tes_id' => ['required', 'uuid', 'exists:program_tes,id'],
        ];
    }
}
