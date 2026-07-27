<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class ReviewQuestionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:approved,rejected'],
            'review_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
