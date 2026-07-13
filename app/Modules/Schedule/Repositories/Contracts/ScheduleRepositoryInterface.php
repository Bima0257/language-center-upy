<?php

namespace App\Modules\Schedule\Repositories\Contracts;

use App\Models\ExamSchedule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ScheduleRepositoryInterface
{
    public function paginateByExam(int $examId, int $perPage = 15): LengthAwarePaginator;
    public function findOrFail(int $id): ExamSchedule;
    public function create(array $data): ExamSchedule;
    public function update(ExamSchedule $schedule, array $data): ExamSchedule;
    public function delete(ExamSchedule $schedule): void;
    public function hasOverlap(array $data): bool;
    public function getAvailableSchedules(): \Illuminate\Support\Collection;
}
