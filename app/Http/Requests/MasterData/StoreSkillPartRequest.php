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
            'skill' => ['required', Rule::enum(SkillCode::class)],
            'name' => ['required', 'string', 'max:100'],
            'order' => ['required', 'integer', 'min:1'],
            'directions' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
