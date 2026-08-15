<?php

namespace App\Http\Requests\Exam;

use App\Enums\SkillCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'question_bank_id' => ['required', 'exists:question_banks,id'],
            'skill' => ['required', Rule::enum(SkillCode::class)],
            'skill_part_id' => ['required', 'exists:skill_parts,id'],
            'passage_id' => ['nullable', 'exists:passages,id'],
            'question_text' => ['nullable', 'string'],
            'option_a' => ['nullable', 'string'],
            'option_b' => ['nullable', 'string'],
            'option_c' => ['nullable', 'string'],
            'option_d' => ['nullable', 'string'],
            'correct_answer' => ['required', 'string', 'max:1', 'in:A,B,C,D'],
            'order' => ['required', 'integer', 'min:1'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
