<?php

namespace App\Http\Requests\Identity;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePermissionModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_module' => ['required', 'string', 'max:100'],
            'module' => ['required', 'string', 'max:100'],
        ];
    }
}
