<?php

namespace App\Modules\Session\DTOs;

class SubmitResult
{
    public function __construct(
        public readonly int $sessionId,
        public readonly float $scoreReading,
        public readonly float $scoreListening,
        public readonly float $scoreTotal,
    ) {}
}
