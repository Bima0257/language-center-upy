<?php

namespace App\Modules\Proctor\Services;

use App\Modules\Session\Repositories\Contracts\ExamSessionRepositoryInterface;
use Illuminate\Support\Collection;

class ProctorService
{
    public function __construct(
        private ExamSessionRepositoryInterface $sessionRepo,
    ) {}

    public function getActiveSessions(): Collection
    {
        return $this->sessionRepo->getActiveSessions();
    }

    public function getFlaggedSessions(bool $includeReviewed = false): Collection
    {
        return $this->sessionRepo->getFlaggedSessions($includeReviewed);
    }
}
