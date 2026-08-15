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
        return ExamSession::with(['violationLogs', 'schedule.exam', 'slot', 'currentSection'])->findOrFail($id);
    }

    public function findWithAnswers(int $id): ?ExamSession
    {
        return ExamSession::with(['answers', 'slot.schedule.exam.sections'])->find($id);
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

    public function hasSessionForSchedule(int $userId, int $scheduleId): bool
    {
        return ExamSession::where('user_id', $userId)
            ->where('exam_schedule_id', $scheduleId)
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
        $query = ExamSession::with(['user', 'violationLogs', 'schedule.exam', 'slot'])
            ->where('is_flagged', true);

        if (! $includeReviewed) {
            $query->whereNull('reviewed_at');
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getActiveSessions(): Collection
    {
        return ExamSession::with(['user', 'schedule.exam', 'slot'])
            ->withCount('answers')
            ->where('status', SessionStatus::IN_PROGRESS)
            ->get();
    }

    public function recentForUser(int $userId, int $limit = 3): Collection
    {
        return ExamSession::with('schedule.exam', 'slot')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }

    public function countActive(): int
    {
        return ExamSession::where('status', SessionStatus::IN_PROGRESS)->count();
    }

    public function countFlaggedPendingReview(): int
    {
        return ExamSession::where('is_flagged', true)->whereNull('reviewed_at')->count();
    }
}
