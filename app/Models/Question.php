<?php

namespace App\Models;

use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $question_bank_id
 * @property int|null $passage_id
 * @property QuestionType $type
 * @property string|null $material_type
 * @property int|null $skill_id
 * @property int|null $skill_part_id
 * @property string $question_text
 * @property string $option_a
 * @property string $option_b
 * @property string $option_c
 * @property string $option_d
 * @property string $correct_answer
 * @property string|null $audio_url
 * @property string|null $image_url
 * @property int $order
 * @property string $status
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $review_note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read QuestionBank $questionBank
 * @property-read Passage|null $passage
 * @property-read Skill|null $skill
 * @property-read SkillPart|null $skillPart
 * @property-read User|null $creator
 * @property-read User|null $updater
 * @property-read User|null $reviewer
 */
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
