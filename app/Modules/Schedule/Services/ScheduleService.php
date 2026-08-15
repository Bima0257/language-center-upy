<?php

namespace App\Modules\Schedule\Services;

use App\Models\ExamSchedule;
use App\Modules\Exam\Repositories\Contracts\ExamRepositoryInterface;
use App\Modules\Schedule\Repositories\Contracts\ScheduleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ScheduleService
{
    public function __construct(
        private ScheduleRepositoryInterface $scheduleRepo,
        private ExamRepositoryInterface $exams,
    ) {}

    public function paginatedByExam(int $examId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->scheduleRepo->paginateByExam($examId, $perPage);
    }

    public function allData(int $perPage = 15): array
    {
        return [
            'schedules' => $this->scheduleRepo->paginateAll($perPage),
            'exams' => $this->exams->allActiveOrdered(),
        ];
    }

    public function paginatedAll(int $perPage = 15): LengthAwarePaginator
    {
        return $this->scheduleRepo->paginateAll($perPage);
    }

    public function create(array $data): ExamSchedule
    {
        if ($this->scheduleRepo->hasOverlap($data)) {
            throw new \RuntimeException('Jadwal bertabrakan dengan jadwal lain pada ujian ini.');
        }

        return $this->scheduleRepo->create($data);
    }

    public function update(ExamSchedule $schedule, array $data): ExamSchedule
    {
        if ($this->scheduleRepo->hasOverlap(array_merge($data, ['exam_id' => $schedule->exam_id]), $schedule->id)) {
            throw new \RuntimeException('Jadwal bertabrakan dengan jadwal lain pada ujian ini.');
        }

        return $this->scheduleRepo->update($schedule, $data);
    }

    public function delete(ExamSchedule $schedule): void
    {
        $this->scheduleRepo->delete($schedule);
    }
}
