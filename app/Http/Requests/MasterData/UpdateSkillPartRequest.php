<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSkillPartRequest extends FormRequest
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
        $partId = $this->route('skillPart')->id;

        return [
            'question_bank_id' => ['required', 'exists:question_banks,id'],
            'skill_id' => ['required', 'exists:skills,id'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('skill_parts', 'name')
                    ->where(function ($query) {
                        return $query
                            ->where('question_bank_id', $this->input('question_bank_id'))
                            ->where('skill_id', $this->input('skill_id'));
                    })
                    ->ignore($partId),
            ],
            'directions' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
