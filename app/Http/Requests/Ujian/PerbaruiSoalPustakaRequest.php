<?php

namespace App\Http\Requests\Ujian;

use App\Models\Soal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PerbaruiSoalPustakaRequest extends FormRequest
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
            'bank_soal_id' => ['required', 'uuid', 'exists:bank_soal,id'],
            'keterampilan_id' => [
                'required',
                'uuid',
                Rule::exists('keterampilan', 'id')->where(fn ($query) => $query->where('aktif', true)),
            ],
            'bagian_soal_id' => ['required', 'uuid', 'exists:bagian_soal,id'],
            'materi_soal_id' => ['nullable', 'uuid', 'exists:materi_soal,id'],
            'teks_soal' => ['nullable', 'string'],
            'pilihan_a' => ['nullable', 'string'],
            'pilihan_b' => ['nullable', 'string'],
            'pilihan_c' => ['nullable', 'string'],
            'pilihan_d' => ['nullable', 'string'],
            'kunci_jawaban' => ['required', 'string', 'max:1', 'in:A,B,C,D'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $soal = $this->route('soal');
            if (! $soal instanceof Soal) {
                return;
            }

            $submittedMateriId = $this->input('materi_soal_id');
            $submittedMateriId = is_string($submittedMateriId) ? $submittedMateriId : null;

            if ($submittedMateriId !== $soal->materi_soal_id) {
                $validator->errors()->add(
                    'materi_soal_id',
                    'Materi soal tidak dapat diubah setelah soal dibuat.',
                );
            }
        }];
    }
}
