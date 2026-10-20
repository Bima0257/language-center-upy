<?php

namespace App\Modules\Keamanan\DTOs;

class DataPelanggaran
{
    public function __construct(
        public readonly string $sesiId,
        public readonly string $jenis,
    ) {}
}
