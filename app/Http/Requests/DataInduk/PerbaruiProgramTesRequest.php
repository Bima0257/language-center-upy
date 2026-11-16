<?php

namespace App\Http\Requests\DataInduk;

use Illuminate\Foundation\Http\FormRequest;

class PerbaruiProgramTesRequest extends FormRequest
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
        $programTesId = $this->route('programTes')->id;

        return [
            'nama' => ['required', 'string', 'max:100', 'unique:program_tes,nama,'.$programTesId],
            'batas_pelanggaran' => ['required', 'integer', 'min:0', 'max:10'],
            'deskripsi' => ['nullable', 'string'],
            'aktif' => ['boolean'],
        ];
    }
}
