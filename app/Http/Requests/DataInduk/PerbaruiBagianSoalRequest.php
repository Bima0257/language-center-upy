<?php

namespace App\Http\Requests\DataInduk;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PerbaruiBagianSoalRequest extends FormRequest
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
        $bagianSoalId = $this->route('bagianSoal')->id;

        return [
            'bank_soal_id' => ['required', 'uuid', 'exists:bank_soal,id'],
            'keterampilan_id' => ['required', 'uuid', 'exists:keterampilan,id'],
            'nama' => [
                'required',
                'string',
                'max:100',
                Rule::unique('bagian_soal', 'nama')
                    ->where(function ($query) {
                        return $query
                            ->where('bank_soal_id', $this->input('bank_soal_id'))
                            ->where('keterampilan_id', $this->input('keterampilan_id'));
                    })
                    ->ignore($bagianSoalId),
            ],
            'petunjuk' => ['nullable', 'string'],
            'aktif' => ['boolean'],
        ];
    }
}
