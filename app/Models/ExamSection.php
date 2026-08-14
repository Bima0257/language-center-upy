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
 * @property int $skill_id
 * @property string $title
 * @property int $order
 * @property int $total_questions
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Exam $exam
 * @property-read Skill $skill
 * @property-read Collection<int, ExamSectionQuestion> $examSectionQuestions
 * @property-read Collection<int, ExamSectionPart> $examSectionParts
 */
#[Fillable(['exam_id', 'skill_id', 'title', 'order', 'total_questions'])]
class ExamSection extends Model
{
    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'total_questions' => 'integer',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function examSectionQuestions(): HasMany
    {
        return $this->hasMany(ExamSectionQuestion::class)->orderBy('order');
    }

    public function examSectionParts(): HasMany
    {
        return $this->hasMany(ExamSectionPart::class)->orderBy('order');
    }
}
