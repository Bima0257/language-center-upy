<?php

namespace App\Modules\Schedule\Repositories;

use App\Models\ExamSchedule;
use App\Modules\Schedule\Repositories\Contracts\ScheduleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ScheduleRepository implements ScheduleRepositoryInterface
{
    public function paginateByExam(int $examId, int $perPage = 15): LengthAwarePaginator
    {
        return ExamSchedule::where('exam_id', $examId)
            ->withCount('sessions')
            ->paginate($perPage);
    }

    public function paginateAll(int $perPage = 15): LengthAwarePaginator
    {
        return ExamSchedule::with('exam')
            ->withCount('sessions')
            ->latest('scheduled_start')
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

    public function hasOverlap(array $data, ?int $excludeId = null): bool
    {
        $start = Carbon::parse($data['scheduled_start']);
        $end = Carbon::parse($data['scheduled_end']);

        return ExamSchedule::where('exam_id', $data['exam_id'])
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where('scheduled_start', '<', $end)
            ->where('scheduled_end', '>', $start)
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

    public function countAvailableNow(): int
    {
        return ExamSchedule::where('is_active', true)
            ->where('scheduled_start', '<=', now())
            ->where('scheduled_end', '>=', now())
            ->count();
    }
}
