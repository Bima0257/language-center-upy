<?php

namespace App\Modules\Proctor\DTOs;

class ReviewDecisionData
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $note,
        public readonly ?int $reviewerId,
    ) {}
}
