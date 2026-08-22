<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class StoreSectionRequest extends FormRequest
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
            'question_bank_id' => ['required', 'exists:question_banks,id'],
            'skill_id' => ['required', 'exists:skills,id'],
            'title' => ['required', 'string', 'max:255'],
            'order' => ['required', 'integer'],
            'total_questions' => ['nullable', 'integer'],
        ];
    }
}
