<?php

namespace App\Http\Requests\Identity;

use App\Enums\JenisPeserta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:100', 'regex:/^\S+$/u', 'unique:users,username'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'status' => ['required', 'boolean'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['uuid', 'exists:roles,id'],
            'jenis_peserta' => ['nullable', Rule::enum(JenisPeserta::class)],
            'nomor_identitas' => ['nullable', 'string', 'max:30'],
            'institusi' => ['nullable', 'string', 'max:150'],
            'program_studi_id' => ['nullable', 'uuid', 'exists:program_studi,id'],
            'tahun_mulai' => ['nullable', 'integer', 'min:2000', 'max:'.(date('Y') + 1)],
            '_query' => ['nullable', 'array'],
        ];
    }
}
