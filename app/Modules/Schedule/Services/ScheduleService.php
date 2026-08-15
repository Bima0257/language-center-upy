<?php

namespace App\Modules\Schedule\Services;

use App\Models\ExamSchedule;
use App\Models\ExamScheduleSlot;
use App\Modules\Exam\Repositories\Contracts\ExamRepositoryInterface;
use App\Modules\Schedule\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Modules\Schedule\Repositories\Contracts\SlotRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class ScheduleService
{
    public function __construct(
        private ScheduleRepositoryInterface $scheduleRepo,
        private SlotRepositoryInterface $slotRepo,
        private ExamRepositoryInterface $exams,
    ) {}

    public function allData(int $perPage = 15): array
    {
        return [
            'schedules' => $this->scheduleRepo->paginateAll($perPage),
            'exams' => $this->exams->allActiveOrdered(),
        ];
    }

    public function showData(ExamSchedule $schedule): array
    {
        return [
            'schedule' => $this->scheduleRepo->findOrFail($schedule->id),
            'slots' => $this->slotRepo->findBySchedule($schedule->id),
        ];
    }

    public function create(array $data): ExamSchedule
    {
        $this->assertNoDateOverlap($data);

        return $this->scheduleRepo->create($data);
    }

    public function update(ExamSchedule $schedule, array $data): ExamSchedule
    {
        $this->assertNoDateOverlap($data, $schedule->id);

        return $this->scheduleRepo->update($schedule, $data);
    }

    public function delete(ExamSchedule $schedule): void
    {
        if ($this->scheduleRepo->hasSlots($schedule)) {
            throw new \RuntimeException('Periode tidak bisa dihapus karena sudah memiliki sesi.');
        }

        $this->scheduleRepo->delete($schedule);
    }

    public function createSlot(ExamSchedule $schedule, array $data): ExamScheduleSlot
    {
        $date = Carbon::parse($data['date']);

        $this->assertDateWithinPeriod($schedule, $date);
        $this->assertNoTimeOverlap($schedule->id, $date, $data['start_time'], $data['end_time']);

        return $this->slotRepo->create([...$data, 'exam_schedule_id' => $schedule->id]);
    }

    public function updateSlot(ExamScheduleSlot $slot, array $data): ExamScheduleSlot
    {
        $date = Carbon::parse($data['date']);

        $this->assertDateWithinPeriod($slot->schedule, $date);
        $this->assertNoTimeOverlap($slot->exam_schedule_id, $date, $data['start_time'], $data['end_time'], $slot->id);

        return $this->slotRepo->update($slot, $data);
    }

    public function deleteSlot(ExamScheduleSlot $slot): void
    {
        if ($this->slotRepo->hasSessions($slot)) {
            throw new \RuntimeException('Sesi tidak bisa dihapus karena sudah diikuti peserta.');
        }

        $this->slotRepo->delete($slot);
    }

    public function availableSlots(): Collection
    {
        return $this->slotRepo->availableSlots();
    }

    public function countAvailableSlotsNow(): int
    {
        return $this->slotRepo->availableSlots()->count();
    }

    private function assertNoDateOverlap(array $data, ?int $excludeId = null): void
    {
        if ($this->scheduleRepo->hasDateOverlap(
            (int) $data['exam_id'],
            Carbon::parse($data['start_date']),
            Carbon::parse($data['end_date']),
            $excludeId,
        )) {
            throw new \RuntimeException('Periode bertabrakan dengan periode lain pada ujian ini.');
        }
    }

    private function assertDateWithinPeriod(ExamSchedule $schedule, Carbon $date): void
    {
        if ($date->toDateString() < $schedule->start_date->toDateString()
            || $date->toDateString() > $schedule->end_date->toDateString()) {
            throw new \RuntimeException('Tanggal sesi harus berada dalam rentang periode ujian.');
        }
    }

    private function assertNoTimeOverlap(int $scheduleId, Carbon $date, string $startTime, string $endTime, ?int $excludeId = null): void
    {
        if ($this->slotRepo->hasTimeOverlap($scheduleId, $date, $startTime, $endTime, $excludeId)) {
            throw new \RuntimeException('Sesi bertabrakan dengan sesi lain pada tanggal yang sama.');
        }
    }
}
