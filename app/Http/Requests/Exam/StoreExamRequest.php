<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'exam_type_id' => ['required', 'exists:exam_types,id'],
            'question_bank_id' => ['required', 'exists:question_banks,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'mode' => ['required', 'in:tryout,official'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:480'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
