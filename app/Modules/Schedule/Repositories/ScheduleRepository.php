<?php

namespace App\Modules\Schedule\Repositories;

use App\Models\ExamSchedule;
use App\Modules\Schedule\Repositories\Contracts\ScheduleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ScheduleRepository implements ScheduleRepositoryInterface
{
    public function paginateByExam(int $examId, int $perPage = 15): LengthAwarePaginator
    {
        return ExamSchedule::where('exam_id', $examId)
            ->withCount('sessions')
            ->paginate($perPage);
    }

    public function findOrFail(int $id): ExamSchedule
    {
        return ExamSchedule::with('exam.sections')->findOrFail($id);
    }

    public function create(array $data): ExamSchedule
    {
        return ExamSchedule::create($data);
    }

    public function update(ExamSchedule $schedule, array $data): ExamSchedule
    {
        $schedule->update($data);
        return $schedule->fresh();
    }

    public function delete(ExamSchedule $schedule): void
    {
        $schedule->delete();
    }

    public function hasOverlap(array $data): bool
    {
        return ExamSchedule::where('exam_id', $data['exam_id'])
            ->where(function ($q) use ($data) {
                $q->whereBetween('scheduled_start', [$data['scheduled_start'], $data['scheduled_end']])
                  ->orWhereBetween('scheduled_end', [$data['scheduled_start'], $data['scheduled_end']]);
            })
            ->exists();
    }

    public function getAvailableSchedules(): Collection
    {
        return ExamSchedule::with('exam')
            ->where('is_active', true)
            ->where('scheduled_start', '<=', now())
            ->where('scheduled_end', '>=', now())
            ->get()
            ->filter(fn ($s) => $s->isAvailable());
    }
}
