<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLibraryQuestionRequest extends FormRequest
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
            'skill_part_id' => ['required', 'exists:skill_parts,id'],
            'passage_id' => ['nullable', 'exists:passages,id'],
            'question_text' => ['nullable', 'string'],
            'option_a' => ['nullable', 'string'],
            'option_b' => ['nullable', 'string'],
            'option_c' => ['nullable', 'string'],
            'option_d' => ['nullable', 'string'],
            'correct_answer' => ['required', 'string', 'max:1', 'in:A,B,C,D'],
        ];
    }
}
