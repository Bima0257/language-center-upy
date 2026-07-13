<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:multiple_choice,multi_select,order,matching,fill_blank,essay,speaking,true_false,dictation,error_id'],
            'question_text' => ['required', 'string'],
            'options' => ['nullable', 'json'],
            'correct_answer' => ['nullable', 'string'],
            'correct_answers' => ['nullable', 'json'],
            'points' => ['integer', 'min:1'],
            'order' => ['required', 'integer', 'min:1'],
            'passage_reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
