<?php

namespace App\Modules\Scoring\Actions;

class CalculateTotalScore
{
    public function execute(
        float $reading,
        float $listening,
        float $speaking = 0,
        float $writing = 0,
    ): float {
        return round($reading + $listening + $speaking + $writing, 1);
    }
}
