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
        $currentJenisTesId = $ujian instanceof Ujian ? $ujian->jenis_tes_id : null;

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
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'mode' => ['required', 'in:tryout,official'],
            'durasi_menit' => ['required', 'integer', 'min:1', 'max:480'],
            'aktif' => ['boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
