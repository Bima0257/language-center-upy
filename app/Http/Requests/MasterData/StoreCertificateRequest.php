<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;

class StoreCertificateRequest extends FormRequest
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
            'exam_session_id' => ['required', 'exists:exam_sessions,id'],
            'certificate_number' => ['required', 'string', 'max:50', 'unique:certificates,certificate_number'],
            'issued_at' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after:issued_at'],
        ];
    }
}
