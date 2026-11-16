<?php

namespace App\Http\Requests\Identity;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $penggunaId = $this->route('pengguna')?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'username' => ['sometimes', 'required', 'string', 'max:100', 'regex:/^\S+$/u', Rule::unique('users', 'username')->ignore($penggunaId)],
            'email' => ['sometimes', 'required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($penggunaId)],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'status' => ['sometimes', 'boolean'],
            'role_ids' => ['sometimes', 'array', 'min:1'],
            'role_ids.*' => ['uuid', 'exists:roles,id'],
            'program_studi_id' => ['nullable', 'uuid', 'exists:program_studi,id'],
            '_query' => ['nullable', 'array'],
        ];
    }
}
