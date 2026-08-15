<?php

namespace App\Http\Requests\MasterData;

use App\Enums\SkillCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSkillPartRequest extends FormRequest
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
            'question_bank_id' => ['required', 'exists:question_banks,id'],
            'skill' => ['required', Rule::enum(SkillCode::class)],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('skill_parts', 'name')->where(function ($query) {
                    return $query
                        ->where('question_bank_id', $this->input('question_bank_id'))
                        ->where('skill', $this->input('skill'));
                }),
            ],
            'order' => ['required', 'integer', 'min:1'],
            'directions' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
