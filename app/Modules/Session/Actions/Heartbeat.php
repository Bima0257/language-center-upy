<?php

namespace App\Modules\Session\Actions;

use App\Enums\SessionStatus;
use App\Modules\Session\Repositories\Contracts\ExamSessionRepositoryInterface;

class Heartbeat
{
    public function __construct(
        private ExamSessionRepositoryInterface $sessionRepo,
    ) {}

    public function execute(int $sessionId): void
    {
        $session = $this->sessionRepo->findOrFail($sessionId);

        if ($session->status !== SessionStatus::IN_PROGRESS) {
            return;
        }

        $this->sessionRepo->update($sessionId, [
            'last_heartbeat_at' => now(),
        ]);

        if ($session->started_at) {
            $elapsedMinutes = now()->diffInMinutes($session->started_at);
            $durationMinutes = $session->schedule?->exam?->duration_minutes ?? 160;

            if ($elapsedMinutes > $durationMinutes + 1) {
                $this->sessionRepo->update($sessionId, [
                    'status' => SessionStatus::TERMINATED,
                    'terminated_at' => now(),
                    'termination_reason' => 'Waktu habis (timeout detected by heartbeat).',
                ]);
            }
        }
    }
}
