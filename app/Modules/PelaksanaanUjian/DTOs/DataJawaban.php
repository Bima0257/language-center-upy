<?php

namespace App\Modules\PelaksanaanUjian\DTOs;

class DataJawaban
{
    public function __construct(
        public readonly string $soalId,
        public readonly string $jawaban,
    ) {}
}
