<?php

namespace App\Models;

use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['question_group_id', 'type', 'skill', 'passage_id', 'audio_file', 'question_text', 'options', 'correct_answer', 'correct_answers', 'points', 'order', 'passage_reference', 'difficulty', 'status', 'created_by', 'updated_by', 'time_estimate', 'explanation', 'reviewed_by', 'reviewed_at', 'review_note'])]
class Question extends Model
{
    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'options' => 'array',
            'correct_answers' => 'array',
            'points' => 'integer',
            'order' => 'integer',
            'time_estimate' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function questionGroup(): BelongsTo
    {
        return $this->belongsTo(QuestionGroup::class);
    }

    public function passage(): BelongsTo
    {
        return $this->belongsTo(Passage::class);
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

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'question_tag');
    }

    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(ExamSection::class, 'exam_section_question')
            ->withPivot('order');
    }
}
