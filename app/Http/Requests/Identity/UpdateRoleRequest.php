<?php

namespace App\Http\Requests\Identity;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'parent_role_ids' => ['sometimes', 'array'],
            'parent_role_ids.*' => ['uuid', 'exists:roles,id'],
            'grant_permission_ids' => ['nullable', 'array'],
            'grant_permission_ids.*' => ['uuid', 'exists:permissions,id'],
            'deny_permission_ids' => ['nullable', 'array'],
            'deny_permission_ids.*' => ['uuid', 'exists:permissions,id'],
            '_query' => ['nullable', 'array'],
        ];
    }
}
