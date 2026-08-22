<?php

namespace App\Modules\Schedule\Repositories;

use App\Enums\ExamMode;
use App\Models\ExamScheduleSlot;
use App\Modules\Schedule\Repositories\Contracts\SlotRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class SlotRepository implements SlotRepositoryInterface
{
    public function findOrFail(int $id): ExamScheduleSlot
    {
        return ExamScheduleSlot::with('schedule.exam')->findOrFail($id);
    }

    public function findBySchedule(int $scheduleId): Collection
    {
        return ExamScheduleSlot::withCount('sessions')
            ->where('exam_schedule_id', $scheduleId)
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
    }

    public function create(array $data): ExamScheduleSlot
    {
        return ExamScheduleSlot::create($data);
    }

    public function update(ExamScheduleSlot $slot, array $data): ExamScheduleSlot
    {
        $slot->update($data);

        return $slot->fresh();
    }

    public function delete(ExamScheduleSlot $slot): void
    {
        $slot->delete();
    }

    public function hasSessions(ExamScheduleSlot $slot): bool
    {
        return $slot->sessions()->exists();
    }

    public function hasTimeOverlap(int $scheduleId, Carbon $date, string $startTime, string $endTime, ?int $excludeId = null): bool
    {
        return ExamScheduleSlot::where('exam_schedule_id', $scheduleId)
            ->whereDate('date', $date->toDateString())
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->exists();
    }

    public function availableSlots(): Collection
    {
        $today = now()->toDateString();

        return ExamScheduleSlot::with('schedule.exam')
            ->where('is_active', true)
            ->whereDate('date', $today)
            ->whereHas('schedule.exam', fn ($q) => $q->where('mode', ExamMode::TRYOUT))
            ->get()
            ->filter(fn ($slot) => $slot->isAvailable());
    }
}
