<?php

namespace App\Modules\Security\Actions;

use App\Enums\SessionStatus;
use App\Enums\ViolationType;
use App\Models\ViolationLog;
use App\Modules\Security\DTOs\StrikeResult;
use App\Modules\Security\Repositories\Contracts\ViolationRepositoryInterface;
use App\Modules\Session\Repositories\Contracts\ExamSessionRepositoryInterface;

class LogViolation
{
    public function __construct(
        private ViolationRepositoryInterface $violationRepo,
        private ExamSessionRepositoryInterface $sessionRepo,
    ) {}

    public function execute(int $sessionId, string $type, array $metadata = []): StrikeResult
    {
        $session = $this->sessionRepo->findOrFail($sessionId);

        if ($session->status !== SessionStatus::IN_PROGRESS) {
            return new StrikeResult(strikeCount: $session->violation_strikes, terminated: false);
        }

        $violationType = ViolationType::tryFrom($type);
        $severity = $violationType?->severity();
        $strikeIncrement = $severity?->strikeIncrement() ?? 1;

        $recent = ViolationLog::where('exam_session_id', $sessionId)
            ->where('type', $type)
            ->where('created_at', '>=', now()->subSeconds(5))
            ->exists();

        if ($recent) {
            return new StrikeResult(strikeCount: $session->violation_strikes, terminated: false);
        }

        $newStrikeCount = $session->violation_strikes + $strikeIncrement;

        $this->violationRepo->create([
            'exam_session_id' => $sessionId,
            'type' => $type,
            'severity' => $severity?->value ?? 'minor',
            'metadata' => $metadata,
            'strike_count' => $newStrikeCount,
        ]);

        $terminated = false;
        $maxStrikes = $session->getMaxStrikes();

        if ($newStrikeCount >= $maxStrikes) {
            $this->sessionRepo->update($sessionId, [
                'status' => SessionStatus::TERMINATED,
                'terminated_at' => now(),
                'termination_reason' => "{$maxStrikes} strikes violation",
                'violation_strikes' => $newStrikeCount,
                'is_flagged' => true,
                'flag_reason' => "Pelanggaran mencapai {$maxStrikes} strike.",
            ]);
            $terminated = true;
        } else {
            $this->sessionRepo->update($sessionId, [
                'violation_strikes' => $newStrikeCount,
            ]);
        }

        activity()
            ->performedOn($session)
            ->withProperties(['type' => $type, 'severity' => $severity?->value, 'strike' => $newStrikeCount])
            ->log("violation:{$type}");

        return new StrikeResult(
            strikeCount: $newStrikeCount,
            terminated: $terminated,
        );
    }
}
