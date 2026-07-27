<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class StoreLibraryQuestionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'skill' => ['required', 'string', 'in:reading,listening,speaking,writing,grammar,vocabulary'],
            'passage_id' => ['nullable', 'exists:passages,id'],
            'audio_file' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:multiple_choice,multi_select,order,matching,fill_blank,essay,speaking,true_false,dictation,error_id'],
            'question_text' => ['required', 'string'],
            'options' => ['nullable', 'json'],
            'correct_answer' => ['nullable', 'string'],
            'points' => ['integer', 'min:1'],
            'passage_reference' => ['nullable', 'string', 'max:255'],
            'difficulty' => ['in:easy,medium,hard'],
            'status' => ['in:draft,submitted,approved,rejected,archived'],
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
