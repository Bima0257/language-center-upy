<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSectionRequest extends FormRequest
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
            'skill_id' => ['exists:skills,id'],
            'title' => ['string', 'max:255'],
            'order' => ['integer'],
            'total_questions' => ['nullable', 'integer'],
        ];
    }
}
