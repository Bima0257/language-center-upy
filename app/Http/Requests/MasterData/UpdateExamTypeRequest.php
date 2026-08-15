<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExamTypeRequest extends FormRequest
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
        $examTypeId = $this->route('examType')->id;

        return [
            'name' => ['required', 'string', 'max:100', 'unique:exam_types,name,'.$examTypeId],
            'max_strikes' => ['required', 'integer', 'min:0', 'max:10'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
