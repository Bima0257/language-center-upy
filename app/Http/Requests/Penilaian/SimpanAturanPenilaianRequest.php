<?php

namespace App\Http\Requests\Penilaian;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SimpanAturanPenilaianRequest extends FormRequest
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
            'tabel_konversi' => ['required', 'array', 'min:1'],
            'tabel_konversi.*' => ['required', 'integer', 'min:0'],
            'skor_mentah_maksimum' => ['required', 'integer', 'min:1', 'max:500'],
            'skor_skala_minimum' => ['required', 'integer', 'min:0', 'max:1000'],
            'skor_skala_maksimum' => ['required', 'integer', 'min:1', 'max:1000', 'gte:skor_skala_minimum'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $maksMentah = (int) $this->input('skor_mentah_maksimum', 0);
            $minSkala = (int) $this->input('skor_skala_minimum', 0);
            $maksSkala = (int) $this->input('skor_skala_maksimum', 0);
            $tabel = (array) $this->input('tabel_konversi', []);

            $kunciTabel = array_map('strval', array_keys($tabel));
            $kunciWajib = array_map('strval', range(0, max(0, $maksMentah)));
            $kunciKurang = array_diff($kunciWajib, $kunciTabel);
            $kunciBerlebih = array_diff($kunciTabel, $kunciWajib);

            if ($kunciKurang !== [] || $kunciBerlebih !== []) {
                $validator->errors()->add(
                    'tabel_konversi',
                    'Tabel konversi harus memiliki tepat satu nilai untuk setiap skor mentah dari 0 sampai maksimum.',
                );
            }

            $nilaiSebelumnya = null;

            for ($skorMentah = 0; $skorMentah <= max(0, $maksMentah); $skorMentah++) {
                $nilai = $tabel[$skorMentah] ?? $tabel[(string) $skorMentah] ?? null;

                if ($nilai === null) {
                    continue;
                }

                $nilai = (int) $nilai;

                if ($nilai < $minSkala || $nilai > $maksSkala) {
                    $validator->errors()->add(
                        'tabel_konversi',
                        'Nilai konversi harus berada di antara skor skala minimum dan maksimum.',
                    );

                    break;
                }

                if ($nilaiSebelumnya !== null && $nilai < $nilaiSebelumnya) {
                    $validator->errors()->add(
                        'tabel_konversi',
                        'Nilai konversi harus monoton naik seiring bertambahnya jawaban benar.',
                    );

                    break;
                }

                $nilaiSebelumnya = $nilai;
            }
        }];
    }
}
