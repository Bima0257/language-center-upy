<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Periode ujian — rentang hari pelaksanaan. Sesi per hari ada di ExamScheduleSlot.
 *
 * @property int $id
 * @property int $exam_id
 * @property string $title
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Exam|null $exam
 * @property-read Collection<int, ExamScheduleSlot> $slots
 * @property-read Collection<int, ExamSession> $sessions
 */
#[Fillable(['exam_id', 'title', 'start_date', 'end_date', 'is_active'])]
class ExamSchedule extends Model
{
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(ExamScheduleSlot::class)->orderBy('date')->orderBy('start_time');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ExamSession::class);
    }

    public function isActive(): bool
    {
        return $this->is_active
            && now()->toDateString() >= $this->start_date->toDateString()
            && now()->toDateString() <= $this->end_date->toDateString();
    }
}
