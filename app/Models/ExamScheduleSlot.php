<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Sesi ujian per hari dalam satu periode (ExamSchedule).
 *
 * @property int $id
 * @property int $exam_schedule_id
 * @property Carbon $date
 * @property string $start_time
 * @property string $end_time
 * @property int $late_tolerance_minutes
 * @property int $max_participants
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ExamSchedule $schedule
 * @property-read Collection<int, ExamSession> $sessions
 */
#[Fillable([
    'exam_schedule_id', 'date', 'start_time', 'end_time',
    'late_tolerance_minutes', 'max_participants', 'is_active',
])]
class ExamScheduleSlot extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'late_tolerance_minutes' => 'integer',
            'max_participants' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ExamSchedule::class, 'exam_schedule_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ExamSession::class, 'exam_schedule_slot_id');
    }

    public function isAvailable(): bool
    {
        if (! $this->is_active || ! $this->schedule->isActive()) {
            return false;
        }

        if (now()->toDateString() !== $this->date->toDateString()) {
            return false;
        }

        $now = now()->format('H:i');
        $start = substr((string) $this->start_time, 0, 5);
        $lateDeadline = Carbon::createFromFormat('H:i', $start)
            ->addMinutes($this->late_tolerance_minutes)
            ->format('H:i');

        if ($now < $start || $now > $lateDeadline) {
            return false;
        }

        return $this->sessions()->count() < $this->max_participants;
    }
}
