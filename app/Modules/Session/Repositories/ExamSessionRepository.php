<?php

namespace App\Modules\Session\Repositories;

use App\Enums\SessionStatus;
use App\Models\ExamSession;
use App\Modules\Session\Repositories\Contracts\ExamSessionRepositoryInterface;
use Illuminate\Support\Collection;

class ExamSessionRepository implements ExamSessionRepositoryInterface
{
    public function findOrFail(int $id): ExamSession
    {
        return ExamSession::with(['violationLogs', 'schedule.exam', 'currentSection'])->findOrFail($id);
    }

    public function findWithAnswers(int $id): ?ExamSession
    {
        return ExamSession::with(['answers.question', 'schedule.exam.sections.questionGroups.questions'])->find($id);
    }

    public function create(array $data): ExamSession
    {
        return ExamSession::create($data);
    }

    public function update(int $id, array $data): ExamSession
    {
        $session = ExamSession::findOrFail($id);
        $session->update($data);
        return $session->fresh();
    }

    public function hasActiveSession(int $userId): bool
    {
        return ExamSession::where('user_id', $userId)
            ->whereIn('status', [SessionStatus::PENDING, SessionStatus::IN_PROGRESS])
            ->exists();
    }

    public function getActiveSessionsBySchedule(int $scheduleId): Collection
    {
        return ExamSession::with('user')
            ->where('exam_schedule_id', $scheduleId)
            ->where('status', SessionStatus::IN_PROGRESS)
            ->get();
    }

    public function getFlaggedSessions(bool $includeReviewed = false): Collection
    {
        $query = ExamSession::with(['user', 'violationLogs', 'schedule.exam'])
            ->where('is_flagged', true);

        if (! $includeReviewed) {
            $query->whereNull('reviewed_at');
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getActiveSessions(): Collection
    {
        return ExamSession::with(['user', 'schedule.exam'])
            ->withCount('answers')
            ->where('status', SessionStatus::IN_PROGRESS)
            ->get();
    }
}
