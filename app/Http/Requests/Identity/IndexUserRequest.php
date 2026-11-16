<?php

namespace App\Http\Requests\Identity;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(['name', 'username', 'email', 'status', 'last_login'])],
            'dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 25, 50, 100])],
            'tab' => ['nullable', Rule::in(['admin', 'peserta', 'custom'])],
            'f_role_names' => ['nullable', 'string', 'max:100'],
            'f_status' => ['nullable', Rule::in(['true', 'false'])],
            'f_program_studi_id' => ['nullable', 'uuid', 'exists:program_studi,id'],
        ];
    }
}
