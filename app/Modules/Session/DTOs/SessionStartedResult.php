<?php

namespace App\Modules\Session\DTOs;

class SessionStartedResult
{
    public function __construct(
        public readonly int $sessionId,
        public readonly ?int $firstSectionId,
    ) {}
}
