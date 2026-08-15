<?php

namespace App\Modules\Schedule\Repositories;

use App\Models\ExamSchedule;
use App\Modules\Schedule\Repositories\Contracts\ScheduleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class ScheduleRepository implements ScheduleRepositoryInterface
{
    public function paginateAll(int $perPage = 15): LengthAwarePaginator
    {
        return ExamSchedule::with('exam')
            ->withCount('slots')
            ->latest('start_date')
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

    public function hasSlots(ExamSchedule $schedule): bool
    {
        return $schedule->slots()->exists();
    }

    public function hasDateOverlap(int $examId, Carbon $startDate, Carbon $endDate, ?int $excludeId = null): bool
    {
        return ExamSchedule::where('exam_id', $examId)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where('start_date', '<=', $endDate->toDateString())
            ->where('end_date', '>=', $startDate->toDateString())
            ->exists();
    }
}
