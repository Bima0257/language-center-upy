<?php

namespace App\Http\Requests\Ujian;

use Illuminate\Foundation\Http\FormRequest;

class PerbaruiMateriSoalRequest extends FormRequest
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
            'judul' => ['required', 'string', 'max:255'],
            'isi_teks' => ['nullable', 'string'],
            'berkas_audio' => ['nullable', 'file', 'mimes:mp3,wav,ogg,m4a', 'max:51200'],
            'berkas_gambar' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
        ];
    }
}
