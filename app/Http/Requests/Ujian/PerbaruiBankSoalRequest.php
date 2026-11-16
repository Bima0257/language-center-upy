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
        $currentProgramTesId = $bankSoal instanceof BankSoal ? $bankSoal->program_tes_id : null;

        return [
            'program_tes_id' => [
                'required',
                'uuid',
                Rule::exists('program_tes', 'id')->where(function ($query) use ($currentProgramTesId) {
                    $query->where('aktif', true);

                    if ($currentProgramTesId !== null) {
                        $query->orWhere('id', $currentProgramTesId);
                    }
                }),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'aktif' => ['boolean'],
        ];
    }
}
