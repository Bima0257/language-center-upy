<?php

namespace App\Http\Requests\Ujian;

use Illuminate\Foundation\Http\FormRequest;

class PerbaruiBagianRequest extends FormRequest
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
            'keterampilan_id' => ['uuid', 'exists:keterampilan,id'],
            'judul' => ['string', 'max:255'],
            'urutan' => ['integer'],
            'jumlah_soal' => ['nullable', 'integer'],
        ];
    }
}
