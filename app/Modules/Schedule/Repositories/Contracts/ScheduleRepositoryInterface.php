<?php

namespace App\Modules\Schedule\Repositories\Contracts;

use App\Models\ExamSchedule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

interface ScheduleRepositoryInterface
{
    public function paginateAll(int $perPage = 15): LengthAwarePaginator;

    public function findOrFail(int $id): ExamSchedule;

    public function create(array $data): ExamSchedule;

    public function update(ExamSchedule $schedule, array $data): ExamSchedule;

    public function delete(ExamSchedule $schedule): void;

    public function hasSlots(ExamSchedule $schedule): bool;

    public function hasDateOverlap(int $examId, Carbon $startDate, Carbon $endDate, ?int $excludeId = null): bool;
}
