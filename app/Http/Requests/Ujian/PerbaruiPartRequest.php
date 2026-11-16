<?php

namespace App\Http\Requests\Ujian;

use Illuminate\Foundation\Http\FormRequest;

class PerbaruiPartRequest extends FormRequest
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
            'section_id' => ['uuid', 'exists:section,id'],
            'judul' => ['string', 'max:255'],
            'urutan' => ['integer'],
        ];
    }
}
