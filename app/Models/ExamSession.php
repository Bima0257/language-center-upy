<?php

namespace App\Models;

use App\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $exam_schedule_id
 * @property int $exam_schedule_slot_id
 * @property int $user_id
 * @property SessionStatus $status
 * @property Carbon|null $started_at
 * @property Carbon|null $submitted_at
 * @property Carbon|null $terminated_at
 * @property string|null $termination_reason
 * @property int|null $current_section_id
 * @property Carbon|null $last_heartbeat_at
 * @property string|null $review_status
 * @property string|null $review_note
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $score_reading
 * @property string|null $score_listening
 * @property string|null $score_speaking
 * @property string|null $score_writing
 * @property string|null $score_total
 * @property bool $is_flagged
 * @property string|null $flag_reason
 * @property int $violation_strikes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ExamSchedule|null $schedule
 * @property-read ExamScheduleSlot|null $slot
 * @property-read User $user
 * @property-read ExamSection|null $currentSection
 * @property-read User|null $reviewer
 * @property-read Collection<int, Answer> $answers
 * @property-read Collection<int, ViolationLog> $violationLogs
 */
#[Fillable([
    'exam_schedule_id', 'exam_schedule_slot_id', 'user_id', 'status', 'started_at', 'submitted_at',
    'terminated_at', 'termination_reason', 'current_section_id',
    'last_heartbeat_at',
    'review_status', 'review_note', 'reviewed_by', 'reviewed_at',
    'score_reading', 'score_listening', 'score_speaking', 'score_writing',
    'score_total', 'is_flagged', 'flag_reason', 'violation_strikes',
])]
class ExamSession extends Model
{
    protected function casts(): array
    {
        return [
            'status' => SessionStatus::class,
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'terminated_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'is_flagged' => 'boolean',
            'violation_strikes' => 'integer',
            'score_reading' => 'decimal:1',
            'score_listening' => 'decimal:1',
            'score_speaking' => 'decimal:1',
            'score_writing' => 'decimal:1',
            'score_total' => 'decimal:1',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ExamSchedule::class, 'exam_schedule_id');
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(ExamScheduleSlot::class, 'exam_schedule_slot_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentSection(): BelongsTo
    {
        return $this->belongsTo(ExamSection::class, 'current_section_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function violationLogs(): HasMany
    {
        return $this->hasMany(ViolationLog::class);
    }

    public function getMaxStrikes(): int
    {
        $examType = $this->schedule?->exam?->examType;

        return $examType !== null ? $examType->max_strikes : 3;
    }
}
