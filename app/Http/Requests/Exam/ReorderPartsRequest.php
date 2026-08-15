<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class ReorderPartsRequest extends FormRequest
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
            'order' => ['required', 'array'],
            'order.*.skill_part_id' => ['required', 'integer', 'distinct'],
            'order.*.order' => ['required', 'integer', 'min:1'],
        ];
    }
}
