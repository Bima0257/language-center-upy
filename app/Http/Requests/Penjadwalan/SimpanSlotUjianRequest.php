<?php

namespace App\Http\Requests\Penjadwalan;

use App\Models\PeriodeUjian;
use Illuminate\Foundation\Http\FormRequest;

class SimpanSlotUjianRequest extends FormRequest
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
        /** @var PeriodeUjian $periode */
        $periode = $this->route('periodeUjian');

        return [
            'tanggal' => ['required', 'date', 'after_or_equal:'.$periode->tanggal_mulai->toDateString(), 'before_or_equal:'.$periode->tanggal_selesai->toDateString()],
            'waktu_mulai' => ['required', 'date_format:H:i'],
            'waktu_selesai' => ['required', 'date_format:H:i', 'after:waktu_mulai'],
            'toleransi_keterlambatan_menit' => ['nullable', 'integer', 'min:0', 'max:180'],
            'kapasitas_peserta' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ];
    }
}
