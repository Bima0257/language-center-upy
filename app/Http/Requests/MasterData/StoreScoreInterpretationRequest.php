<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;

class StoreScoreInterpretationRequest extends FormRequest
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
            'exam_type_id' => ['required', 'exists:exam_types,id'],
            'min_score' => ['required', 'integer', 'min:0'],
            'max_score' => ['required', 'integer', 'gte:min_score'],
            'cefr_level' => ['required', 'string', 'max:10'],
            'level_label' => ['required', 'string', 'max:50'],
            'is_passing' => ['boolean'],
            'description' => ['nullable', 'string'],
        ];
    }
}
