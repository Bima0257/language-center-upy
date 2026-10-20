<?php

namespace App\Http\Requests\Ujian;

use Illuminate\Foundation\Http\FormRequest;

class LampirkanSoalBagianRequest extends FormRequest
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
            'soal_ids' => ['required', 'array', 'min:1'],
            'soal_ids.*' => ['uuid', 'distinct'],
        ];
    }
}
