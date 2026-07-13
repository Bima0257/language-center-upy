<?php

namespace App\Modules\Security\Actions;

use App\Enums\SessionStatus;
use App\Modules\Session\Repositories\Contracts\ExamSessionRepositoryInterface;

class TerminateSession
{
    public function __construct(
        private ExamSessionRepositoryInterface $sessionRepo,
    ) {}

    public function execute(int $sessionId, string $reason): void
    {
        $this->sessionRepo->update($sessionId, [
            'status' => SessionStatus::TERMINATED,
            'terminated_at' => now(),
            'termination_reason' => $reason,
            'is_flagged' => true,
            'flag_reason' => $reason,
        ]);

        activity()
            ->performedOn(\App\Models\ExamSession::find($sessionId))
            ->withProperties(['reason' => $reason])
            ->log('session_terminated');
    }
}
