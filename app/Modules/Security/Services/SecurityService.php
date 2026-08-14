<?php

namespace App\Modules\Security\Services;

use App\Models\ExamSession;
use App\Modules\Security\Repositories\Contracts\ViolationRepositoryInterface;

class SecurityService
{
    public function __construct(
        private ViolationRepositoryInterface $violationRepo,
    ) {}

    public function logSystemEvent(int $sessionId, string $event): void
    {
        activity()
            ->performedOn(ExamSession::findOrFail($sessionId))
            ->log($event);
    }

    public function getStrikeCount(int $sessionId): int
    {
        return $this->violationRepo->countBySession($sessionId);
    }

    public function isStrikeLimitReached(int $sessionId): bool
    {
        return $this->getStrikeCount($sessionId) >= 3;
    }
}
