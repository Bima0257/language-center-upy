<?php

namespace App\Modules\Ujian\DTOs;

class DataSoal
{
    public function __construct(
        public readonly string $jenis,
        public readonly string $teks_soal,
        public readonly string $pilihan_a,
        public readonly string $pilihan_b,
        public readonly string $pilihan_c,
        public readonly string $pilihan_d,
        public readonly string $kunci_jawaban,
        public readonly int $urutan = 0,
    ) {}
}
