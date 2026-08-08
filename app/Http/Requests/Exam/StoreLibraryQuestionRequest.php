<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class StoreLibraryQuestionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'question_bank_id' => ['required', 'exists:question_banks,id'],
            'passage_id' => ['nullable', 'exists:passages,id', 'prohibits:new_passage_title'],
            'new_passage_title' => ['nullable', 'string', 'max:255', 'prohibits:passage_id'],
            'new_passage_type' => ['required_with:new_passage_title', 'in:text,audio,image,prompt'],
            'new_passage_content_text' => ['nullable', 'string'],
            'new_passage_audio_file' => ['nullable', 'file', 'mimes:mp3,wav,ogg,m4a', 'max:51200'],
            'new_passage_image_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'questions' => ['required', 'array', 'min:1', 'max:50'],
            'questions.*.skill_id' => ['required', 'exists:skills,id'],
            'questions.*.skill_part_id' => ['required', 'exists:skill_parts,id'],
            'questions.*.question_text' => ['nullable', 'string'],
            'questions.*.option_a' => ['nullable', 'string'],
            'questions.*.option_b' => ['nullable', 'string'],
            'questions.*.option_c' => ['nullable', 'string'],
            'questions.*.option_d' => ['nullable', 'string'],
            'questions.*.correct_answer' => ['required', 'string', 'max:1', 'in:A,B,C,D'],
            'questions.*.audio_file' => ['nullable', 'file', 'mimes:mp3,wav,ogg,m4a', 'max:51200'],
            'questions.*.image_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
