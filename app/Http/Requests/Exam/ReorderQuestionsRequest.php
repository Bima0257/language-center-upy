<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class ReorderQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'orders' => ['required', 'array'],
            'orders.*.question_id' => ['required', 'integer', 'distinct'],
            'orders.*.number' => ['required', 'integer', 'min:1'],
        ];
    }
}
