<?php

namespace App\Modules\Session\Actions;

use App\Enums\SessionStatus;
use App\Modules\Schedule\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Modules\Security\Services\SecurityService;
use App\Modules\Session\DTOs\SessionStartedResult;
use App\Modules\Session\Repositories\Contracts\ExamSessionRepositoryInterface;

class StartExamSession
{
    public function __construct(
        private ExamSessionRepositoryInterface $sessionRepo,
        private ScheduleRepositoryInterface $scheduleRepo,
        private SecurityService $security,
    ) {}

    public function execute(int $userId, int $scheduleId): SessionStartedResult
    {
        $schedule = $this->scheduleRepo->findOrFail($scheduleId);

        if (! $schedule->isAvailable()) {
            throw new \RuntimeException('Sesi ujian tidak tersedia atau sudah penuh.');
        }

        if ($this->sessionRepo->hasActiveSession($userId)) {
            throw new \RuntimeException('Anda sudah memiliki sesi ujian yang aktif.');
        }

        $firstSection = $schedule->exam->sections->first();

        $session = $this->sessionRepo->create([
            'exam_schedule_id' => $schedule->id,
            'user_id' => $userId,
            'status' => SessionStatus::IN_PROGRESS,
            'started_at' => now(),
            'current_section_id' => $firstSection?->id,
        ]);

        $this->security->logSystemEvent($session->id, 'exam_started');

        return new SessionStartedResult(
            sessionId: $session->id,
            firstSectionId: $firstSection?->id,
        );
    }
}
