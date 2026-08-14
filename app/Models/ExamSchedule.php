<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $exam_id
 * @property string $title
 * @property Carbon $scheduled_start
 * @property Carbon $scheduled_end
 * @property int $late_tolerance_minutes
 * @property int $max_participants
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Exam|null $exam
 * @property-read Collection<int, ExamSession> $sessions
 */
#[Fillable(['exam_id', 'title', 'scheduled_start', 'scheduled_end', 'late_tolerance_minutes', 'max_participants', 'is_active'])]
class ExamSchedule extends Model
{
    protected function casts(): array
    {
        return [
            'scheduled_start' => 'datetime',
            'scheduled_end' => 'datetime',
            'late_tolerance_minutes' => 'integer',
            'max_participants' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ExamSession::class);
    }

    public function isAvailable(): bool
    {
        return $this->is_active
            && now()->between($this->scheduled_start, $this->scheduled_end)
            && now()->lte($this->scheduled_start->copy()->addMinutes($this->late_tolerance_minutes))
            && $this->sessions()->count() < $this->max_participants;
    }
}
