<?php

namespace App\Modules\Security\DTOs;

class StrikeResult
{
    public function __construct(
        public readonly int $strikeCount,
        public readonly bool $terminated,
    ) {}
}
