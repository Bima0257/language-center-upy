<?php

namespace App\Http\Requests\Exam;

use App\Enums\SkillCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSectionRequest extends FormRequest
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
            'skill' => [Rule::enum(SkillCode::class)],
            'title' => ['string', 'max:255'],
            'order' => ['integer'],
            'total_questions' => ['nullable', 'integer'],
        ];
    }
}
