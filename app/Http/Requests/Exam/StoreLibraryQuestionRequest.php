<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class StoreLibraryQuestionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'exam_id' => ['required', 'exists:exams,id'],
            'section_type' => ['required', 'in:reading,listening,speaking,writing,grammar,vocabulary'],
            'group_title' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:multiple_choice,multi_select,order,matching,fill_blank,essay,speaking,true_false,dictation,error_id'],
            'question_text' => ['required', 'string'],
            'options' => ['nullable', 'json'],
            'correct_answer' => ['nullable', 'string'],
            'points' => ['integer', 'min:1'],
            'passage_reference' => ['nullable', 'string', 'max:255'],
            'difficulty' => ['in:easy,medium,hard'],
            'status' => ['in:draft,active,archived'],
            'explanation' => ['nullable', 'string'],
            'time_estimate' => ['nullable', 'integer', 'min:1', 'max:600'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
