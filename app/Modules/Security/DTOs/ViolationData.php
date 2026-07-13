<?php

namespace App\Modules\Security\DTOs;

class ViolationData
{
    public function __construct(
        public readonly int $sessionId,
        public readonly string $type,
        public readonly ?array $metadata = null,
    ) {}
}
