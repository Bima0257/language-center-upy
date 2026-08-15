<?php

namespace App\Modules\Schedule\Repositories\Contracts;

use App\Models\ExamScheduleSlot;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

interface SlotRepositoryInterface
{
    public function findOrFail(int $id): ExamScheduleSlot;

    public function findBySchedule(int $scheduleId): Collection;

    public function create(array $data): ExamScheduleSlot;

    public function update(ExamScheduleSlot $slot, array $data): ExamScheduleSlot;

    public function delete(ExamScheduleSlot $slot): void;

    public function hasSessions(ExamScheduleSlot $slot): bool;

    public function hasTimeOverlap(int $scheduleId, Carbon $date, string $startTime, string $endTime, ?int $excludeId = null): bool;

    public function availableSlots(): Collection;
}
