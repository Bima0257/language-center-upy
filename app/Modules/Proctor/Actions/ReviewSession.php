<?php

namespace App\Modules\Proctor\Actions;

use App\Enums\SessionStatus;
use App\Modules\Proctor\DTOs\ReviewDecisionData;
use App\Modules\Session\Repositories\Contracts\ExamSessionRepositoryInterface;

class ReviewSession
{
    public function __construct(
        private ExamSessionRepositoryInterface $sessionRepo,
    ) {}

    public function execute(int $sessionId, ReviewDecisionData $decision): void
    {
        $this->sessionRepo->update($sessionId, [
            'status' => SessionStatus::REVIEWED,
            'review_status' => $decision->status,
            'review_note' => $decision->note,
            'reviewed_by' => $decision->reviewerId,
            'reviewed_at' => now(),
        ]);

        activity()
            ->performedOn(\App\Models\ExamSession::find($sessionId))
            ->withProperties(['decision' => $decision->status])
            ->log('review:' . $decision->status);
    }
}
