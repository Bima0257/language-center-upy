<?php

namespace App\Http\Requests\Exam;

use App\Enums\SkillCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkImportQuestionsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'questions' => ['required', 'array', 'min:1', 'max:500'],
            'questions.*.question_bank_id' => ['required', 'exists:question_banks,id'],
            'questions.*.skill' => ['required', Rule::enum(SkillCode::class)],
            'questions.*.skill_part_id' => ['nullable', 'exists:skill_parts,id'],
            'questions.*.passage_id' => ['nullable', 'exists:passages,id'],
            'questions.*.question_text' => ['nullable', 'string'],
            'questions.*.option_a' => ['nullable', 'string'],
            'questions.*.option_b' => ['nullable', 'string'],
            'questions.*.option_c' => ['nullable', 'string'],
            'questions.*.option_d' => ['nullable', 'string'],
            'questions.*.correct_answer' => ['required', 'string', 'max:1', 'in:A,B,C,D'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
