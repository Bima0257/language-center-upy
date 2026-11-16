<?php

namespace App\Modules\Penilaian\Actions;

class HitungSkorTotal
{
    /**
     * @param  array<int, float>  $skorSection
     */
    public function execute(array $skorSection): float
    {
        return round(array_sum($skorSection), 1);
    }
}
