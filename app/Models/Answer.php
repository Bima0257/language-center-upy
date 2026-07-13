<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['exam_session_id', 'question_id', 'answer_text', 'answer_json', 'audio_file', 'audio_duration', 'is_correct', 'score', 'scorer_id', 'scored_at'])]
class Answer extends Model
{
    protected function casts(): array
    {
        return [
            'answer_json' => 'array',
            'is_correct' => 'boolean',
            'score' => 'decimal:2',
            'audio_duration' => 'integer',
            'scored_at' => 'datetime',
        ];
    }

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function scorer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scorer_id');
    }
}
