<?php

namespace App\Http\Requests\PendaftaranUjian;

use Illuminate\Foundation\Http\FormRequest;

class TinjauPendaftaranUjianRequest extends FormRequest
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
            'catatan_admin' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
