<?php

namespace App\Http\Requests\Ujian;

use Illuminate\Foundation\Http\FormRequest;

class TinjauSoalRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:approved,rejected'],
            'catatan_tinjauan' => ['nullable', 'string', 'max:1000'],
            'ids' => ['sometimes', 'array', 'min:1'],
            'ids.*' => ['uuid', 'exists:soal,id'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
