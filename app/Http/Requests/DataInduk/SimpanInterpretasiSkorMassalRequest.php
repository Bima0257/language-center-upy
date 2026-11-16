<?php

namespace App\Http\Requests\DataInduk;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SimpanInterpretasiSkorMassalRequest extends FormRequest
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
        $skemaPenilaianId = (string) $this->input('skema_penilaian_id', '');

        return [
            'skema_penilaian_id' => ['required', 'uuid', 'exists:skema_penilaian,id'],
            'interpretasi_skor' => ['required', 'array', 'min:1', 'max:10'],
            'interpretasi_skor.*.skor_minimum' => [
                'required',
                'integer',
                'min:0',
                'distinct',
                Rule::unique('interpretasi_skor', 'skor_minimum')
                    ->where(fn ($query) => $query->where('skema_penilaian_id', $skemaPenilaianId)),
            ],
            'interpretasi_skor.*.skor_maksimum' => ['required', 'integer', 'min:0'],
            'interpretasi_skor.*.tingkat_cefr' => ['required', 'string', 'max:10'],
            'interpretasi_skor.*.label_tingkat' => ['required', 'string', 'max:50'],
            'interpretasi_skor.*.lulus' => ['boolean'],
            'interpretasi_skor.*.deskripsi' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ((array) $this->input('interpretasi_skor', []) as $index => $interpretasi) {
                if (! is_array($interpretasi)) {
                    continue;
                }

                $minimum = $interpretasi['skor_minimum'] ?? null;
                $maksimum = $interpretasi['skor_maksimum'] ?? null;

                if (is_numeric($minimum) && is_numeric($maksimum) && (int) $maksimum < (int) $minimum) {
                    $validator->errors()->add(
                        "interpretasi_skor.{$index}.skor_maksimum",
                        'Skor maksimum harus lebih besar atau sama dengan skor minimum.',
                    );
                }
            }
        }];
    }
}
