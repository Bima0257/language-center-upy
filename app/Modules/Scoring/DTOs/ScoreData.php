<?php

namespace App\Modules\Scoring\DTOs;

class ScoreData
{
    public function __construct(
        public readonly float $reading,
        public readonly float $listening,
        public readonly float $speaking = 0,
        public readonly float $writing = 0,
        public readonly float $total = 0,
    ) {}
}
