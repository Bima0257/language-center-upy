<?php

namespace App\Models;

use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'question_bank_id', 'passage_id', 'type', 'material_type', 'skill_id', 'skill_part_id',
    'question_text', 'option_a', 'option_b', 'option_c', 'option_d',
    'correct_answer', 'audio_url', 'image_url', 'order', 'status',
    'created_by', 'updated_by', 'reviewed_by', 'reviewed_at', 'review_note',
])]
class Question extends Model
{
    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'order' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function questionBank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class);
    }

    public function passage(): BelongsTo
    {
        return $this->belongsTo(Passage::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function skillPart(): BelongsTo
    {
        return $this->belongsTo(SkillPart::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
