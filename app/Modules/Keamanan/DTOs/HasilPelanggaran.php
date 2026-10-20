<?php

namespace App\Modules\Keamanan\DTOs;

class HasilPelanggaran
{
    public function __construct(
        public readonly int $jumlahPelanggaran,
        public readonly bool $dihentikan,
    ) {}
}
