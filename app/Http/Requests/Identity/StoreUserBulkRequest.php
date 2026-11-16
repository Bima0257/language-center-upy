<?php

namespace App\Http\Requests\Identity;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserBulkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'users' => ['required', 'array', 'min:1', 'max:10'],
            'users.*.name' => ['required', 'string', 'max:100'],
            'users.*.username' => ['required', 'string', 'max:100', 'regex:/^\S+$/u', 'unique:users,username'],
            'users.*.email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'users.*.password' => ['required', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'users.*.status' => ['required', 'boolean'],
            'users.*.role_ids' => ['required', 'array', 'min:1'],
            'users.*.role_ids.*' => ['uuid', 'exists:roles,id'],
            'users.*.program_studi_id' => ['nullable', 'uuid', 'exists:program_studi,id'],
            '_query' => ['nullable', 'array'],
        ];
    }
}
