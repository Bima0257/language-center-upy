<?php

namespace App\Http\Requests\Ujian;

use Illuminate\Foundation\Http\FormRequest;

class SimpanJawabanDemoRequest extends FormRequest
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
            'jawaban' => ['present', 'array'],
            'jawaban.*.soal_id' => ['required', 'uuid', 'distinct'],
            'jawaban.*.jawaban' => ['nullable', 'string', 'in:A,B,C,D'],
        ];
    }
}
