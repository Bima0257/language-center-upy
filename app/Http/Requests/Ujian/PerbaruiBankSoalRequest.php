<?php

namespace App\Http\Requests\Ujian;

use App\Models\BankSoal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PerbaruiBankSoalRequest extends FormRequest
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
        $bankSoal = $this->route('bankSoal');
        $currentJenisTesId = $bankSoal instanceof BankSoal ? $bankSoal->jenis_tes_id : null;

        return [
            'jenis_tes_id' => [
                'required',
                'uuid',
                Rule::exists('jenis_tes', 'id')->where(function ($query) use ($currentJenisTesId) {
                    $query->where('aktif', true);

                    if ($currentJenisTesId !== null) {
                        $query->orWhere('id', $currentJenisTesId);
                    }
                }),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'aktif' => ['boolean'],
        ];
    }
}
