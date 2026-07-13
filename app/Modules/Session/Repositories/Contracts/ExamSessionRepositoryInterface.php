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
    public function getActiveSessionsBySchedule(int $scheduleId): Collection;
    public function getFlaggedSessions(bool $includeReviewed = false): Collection;
    public function getActiveSessions(): Collection;
}
