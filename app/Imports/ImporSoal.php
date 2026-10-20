<?php

namespace App\Imports;

use App\Models\Soal;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ImporSoal implements ToModel, WithHeadingRow
{
    private int $order;

    public function __construct(int $startOrder = 0)
    {
        $this->order = $startOrder;
    }

    public function model(array $row)
    {
        $this->order++;

        return new Soal([
            'bank_soal_id' => $row['bank_soal_id'] ?? null,
            'keterampilan_id' => $row['keterampilan_id'] ?? null,
            'bagian_soal_id' => $row['bagian_soal_id'] ?? null,
            'materi_soal_id' => $row['materi_soal_id'] ?? null,
            'teks_soal' => $row['teks_soal'] ?? '',
            'pilihan_a' => $row['pilihan_a'] ?? '',
            'pilihan_b' => $row['pilihan_b'] ?? '',
            'pilihan_c' => $row['pilihan_c'] ?? '',
            'pilihan_d' => $row['pilihan_d'] ?? '',
            'kunci_jawaban' => $row['kunci_jawaban'] ?? null,
            'urutan' => $this->order,
        ]);
    }
}
