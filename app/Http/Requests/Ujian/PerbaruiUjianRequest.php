<?php

namespace App\Http\Requests\Ujian;

use App\Models\Ujian;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PerbaruiUjianRequest extends FormRequest
{
    public function rules(): array
    {
        $ujian = $this->route('ujian');
        $currentProgramTesId = $ujian instanceof Ujian ? $ujian->program_tes_id : null;

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
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'mode' => ['required', 'in:tryout,official'],
            'durasi_menit' => ['required', 'integer', 'min:1', 'max:480'],
            'lingkup' => ['required', 'in:umum,prodi'],
            'program_studi_ids' => [
                'exclude_unless:lingkup,prodi',
                'array',
                Rule::requiredIf(fn (): bool => $this->input('lingkup') === 'prodi'),
                'min:1',
            ],
            'program_studi_ids.*' => ['uuid', 'exists:program_studi,id'],
            'aktif' => ['boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
