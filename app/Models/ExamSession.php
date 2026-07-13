<?php

namespace App\Models;

use App\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'exam_schedule_id', 'user_id', 'status', 'started_at', 'submitted_at',
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

    public function flaggedQuestions(): HasMany
    {
        return $this->hasMany(FlaggedQuestion::class);
    }

    public function violationLogs(): HasMany
    {
        return $this->hasMany(ViolationLog::class);
    }

    public function getMaxStrikes(): int
    {
        return $this->schedule?->exam?->examType?->max_strikes ?? 3;
    }
}
