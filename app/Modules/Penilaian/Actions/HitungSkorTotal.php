<?php

namespace App\Modules\Penilaian\Actions;

class HitungSkorTotal
{
    public function execute(
        float $membaca,
        float $menyimak,
        float $berbicara = 0,
        float $menulis = 0,
    ): float {
        return round($membaca + $menyimak + $berbicara + $menulis, 1);
    }
}
