<?php

namespace App\Http\Requests\DataInduk;

use Illuminate\Foundation\Http\FormRequest;

class PerbaruiJenisTesRequest extends FormRequest
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
        $jenisTesId = $this->route('jenisTes')->id;

        return [
            'nama' => ['required', 'string', 'max:100', 'unique:jenis_tes,nama,'.$jenisTesId],
            'batas_pelanggaran' => ['required', 'integer', 'min:0', 'max:10'],
            'deskripsi' => ['nullable', 'string'],
            'aktif' => ['boolean'],
        ];
    }
}
