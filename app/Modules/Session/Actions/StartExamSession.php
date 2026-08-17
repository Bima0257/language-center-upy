<?php

namespace App\Modules\Session\Actions;

use App\Enums\SessionStatus;
use App\Modules\Schedule\Repositories\Contracts\SlotRepositoryInterface;
use App\Modules\Security\Services\SecurityService;
use App\Modules\Session\DTOs\SessionStartedResult;
use App\Modules\Session\Repositories\Contracts\ExamSessionRepositoryInterface;

class StartExamSession
{
    public function __construct(
        private ExamSessionRepositoryInterface $sessionRepo,
        private SlotRepositoryInterface $slotRepo,
        private SecurityService $security,
    ) {}

    public function execute(int $userId, int $slotId): SessionStartedResult
    {
        $slot = $this->slotRepo->findOrFail($slotId);

        if (! $slot->isAvailable()) {
            throw new \RuntimeException('Sesi ujian tidak tersedia atau sudah penuh.');
        }

        if ($this->sessionRepo->hasSessionForSchedule($userId, $slot->exam_schedule_id)) {
            throw new \RuntimeException('Anda sudah mengikuti ujian pada periode ini. Satu percobaan per periode.');
        }

        if ($this->sessionRepo->hasActiveSession($userId)) {
            throw new \RuntimeException('Anda sudah memiliki sesi ujian yang aktif.');
        }

        $firstSection = $slot->schedule->exam->sections->first();

        $session = $this->sessionRepo->create([
            'exam_schedule_slot_id' => $slot->id,
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
