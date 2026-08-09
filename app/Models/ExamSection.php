<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
