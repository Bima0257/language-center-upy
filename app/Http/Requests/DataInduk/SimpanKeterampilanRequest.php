<?php

namespace App\Http\Requests\DataInduk;

use App\Enums\KetentuanMedia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanKeterampilanRequest extends FormRequest
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
            'jenis_tes_id' => [
                'required',
                'uuid',
                Rule::exists('jenis_tes', 'id')->where(fn ($query) => $query->where('aktif', true)),
            ],
            'nama' => [
                'required',
                'string',
                'max:100',
                Rule::unique('keterampilan', 'nama')->where(fn ($query) => $query->where('jenis_tes_id', $this->input('jenis_tes_id'))),
            ],
            'deskripsi' => ['nullable', 'string'],
            'urutan' => ['nullable', 'integer', 'min:1'],
            'aktif' => ['boolean'],
            'ketentuan_materi' => ['nullable', Rule::enum(KetentuanMedia::class)],
            'aturan_media' => ['nullable', 'array:text,audio,image'],
            'aturan_media.text' => ['nullable', Rule::enum(KetentuanMedia::class)],
            'aturan_media.audio' => ['nullable', Rule::enum(KetentuanMedia::class)],
            'aturan_media.image' => ['nullable', Rule::enum(KetentuanMedia::class)],
        ];
    }
}
