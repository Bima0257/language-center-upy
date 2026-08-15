<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class SaveArrangementRequest extends FormRequest
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
            'part_order' => ['sometimes', 'array'],
            'part_order.*.skill_part_id' => ['required', 'integer', 'distinct'],
            'part_order.*.order' => ['required', 'integer', 'min:1'],
            'question_orders' => ['sometimes', 'array'],
            'question_orders.*.question_id' => ['required', 'integer', 'distinct'],
            'question_orders.*.number' => ['required', 'integer', 'min:1'],
        ];
    }
}
