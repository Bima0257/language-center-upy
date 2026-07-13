<?php

namespace App\Modules\Security\Actions;

use App\Modules\Session\Repositories\Contracts\ExamSessionRepositoryInterface;

class ProcessStrike
{
    public function __construct(
        private ExamSessionRepositoryInterface $sessionRepo,
    ) {}

    public function execute(int $sessionId): array
    {
        $session = $this->sessionRepo->findOrFail($sessionId);
        $strikes = $session->violation_strikes;
        $maxStrikes = $session->getMaxStrikes();

        return [
            'strikes' => $strikes,
            'isTerminated' => $strikes >= $maxStrikes,
            'remainingWarnings' => max(0, $maxStrikes - $strikes),
            'maxStrikes' => $maxStrikes,
        ];
    }
}
