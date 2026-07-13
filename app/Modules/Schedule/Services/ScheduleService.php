<?php

namespace App\Modules\Schedule\Services;

use App\Models\ExamSchedule;
use App\Modules\Schedule\Repositories\Contracts\ScheduleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ScheduleService
{
    public function __construct(
        private ScheduleRepositoryInterface $scheduleRepo,
    ) {}

    public function paginatedByExam(int $examId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->scheduleRepo->paginateByExam($examId, $perPage);
    }

    public function create(array $data): ExamSchedule
    {
        return $this->scheduleRepo->create($data);
    }

    public function delete(ExamSchedule $schedule): void
    {
        $this->scheduleRepo->delete($schedule);
    }
}
