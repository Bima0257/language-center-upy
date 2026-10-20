<?php

namespace App\Modules\Penilaian\DTOs;

class DataSkor
{
    public function __construct(
        public readonly float $membaca,
        public readonly float $menyimak,
        public readonly float $berbicara = 0,
        public readonly float $menulis = 0,
        public readonly float $total = 0,
    ) {}
}
