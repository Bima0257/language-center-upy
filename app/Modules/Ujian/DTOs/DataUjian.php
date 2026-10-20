<?php

namespace App\Modules\Ujian\DTOs;

class DataUjian
{
    public function __construct(
        public readonly string $judul,
        public readonly ?string $deskripsi,
        public readonly string $jenis = 'tryout',
        public readonly int $durasi_menit = 160,
        public readonly bool $aktif = true,
    ) {}
}
