<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class BulkImportQuestionsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'questions' => ['required', 'array', 'min:1', 'max:500'],
            'questions.*.type' => ['required', 'in:multiple_choice,multi_select,order,matching,fill_blank,essay,speaking,true_false,dictation,error_id'],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.correct_answer' => ['nullable', 'string'],
            'questions.*.points' => ['integer', 'min:1'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
