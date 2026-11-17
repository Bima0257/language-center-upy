<?php

namespace App\Http\Requests\Identity;

use App\Enums\JenisPeserta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
            'users.*.jenis_peserta' => ['nullable', Rule::enum(JenisPeserta::class)],
            'users.*.nomor_identitas' => ['nullable', 'string', 'max:30'],
            'users.*.institusi' => ['nullable', 'string', 'max:150'],
            'users.*.program_studi_id' => ['nullable', 'uuid', 'exists:program_studi,id'],
            'users.*.tahun_mulai' => ['nullable', 'integer', 'min:2000', 'max:'.(date('Y') + 1)],
            '_query' => ['nullable', 'array'],
        ];
    }
}
