<?php

namespace App\Http\Requests\Ujian;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanSoalPustakaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'bank_soal_id' => ['required', 'uuid', 'exists:bank_soal,id'],
            'materi_soal_id' => ['nullable', 'uuid', 'exists:materi_soal,id', 'prohibits:materi_baru_judul'],
            'materi_baru_judul' => ['nullable', 'string', 'max:255', 'prohibits:materi_soal_id'],
            'materi_baru_isi_teks' => ['nullable', 'string'],
            'materi_baru_berkas_audio' => ['nullable', 'file', 'mimes:mp3,wav,ogg,m4a', 'max:51200'],
            'materi_baru_berkas_gambar' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:'.config('image.max_kb'),
                'dimensions:max_width=12000,max_height=12000',
            ],
            'soal' => ['required', 'array', 'min:1', 'max:50'],
            'soal.*.section_id' => [
                'required',
                'uuid',
                Rule::exists('section', 'id')->where(fn ($query) => $query->where('aktif', true)),
            ],
            'soal.*.part_soal_id' => ['required', 'uuid', 'exists:part_soal,id'],
            'soal.*.teks_soal' => ['nullable', 'string'],
            'soal.*.pilihan_a' => ['nullable', 'string'],
            'soal.*.pilihan_b' => ['nullable', 'string'],
            'soal.*.pilihan_c' => ['nullable', 'string'],
            'soal.*.pilihan_d' => ['nullable', 'string'],
            'soal.*.kunci_jawaban' => ['required', 'string', 'max:1', 'in:A,B,C,D'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
