<?php

namespace App\Modules\Session\Repositories\Contracts;

use App\Models\ExamSession;
use Illuminate\Support\Collection;

interface ExamSessionRepositoryInterface
{
    public function findOrFail(int $id): ExamSession;

    public function findWithAnswers(int $id): ?ExamSession;

    public function create(array $data): ExamSession;

    public function update(int $id, array $data): ExamSession;

    public function hasActiveSession(int $userId): bool;

    public function findActiveSession(int $userId): ?ExamSession;

    public function hasSessionForSchedule(int $userId, int $scheduleId): bool;

    public function getActiveSessionsBySchedule(int $scheduleId): Collection;

    public function getFlaggedSessions(bool $includeReviewed = false): Collection;

    public function getActiveSessions(): Collection;

    public function recentForUser(int $userId, int $limit = 3): Collection;

    public function countActive(): int;

    public function countFlaggedPendingReview(): int;
}
