<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'question_bank_id' => ['required', 'exists:question_banks,id'],
            'skill_id' => ['required', 'exists:skills,id'],
            'passage_id' => ['nullable', 'exists:passages,id'],
            'question_text' => ['nullable', 'string'],
            'option_a' => ['nullable', 'string'],
            'option_b' => ['nullable', 'string'],
            'option_c' => ['nullable', 'string'],
            'option_d' => ['nullable', 'string'],
            'correct_answer' => ['required', 'string', 'max:1', 'in:A,B,C,D'],
            'order' => ['required', 'integer', 'min:1'],
            'audio_file' => ['nullable', 'file', 'mimes:mp3,wav,ogg,m4a', 'max:51200'],
            'image_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
