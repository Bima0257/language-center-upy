<?php

namespace App\Models;

use App\Enums\SectionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['exam_id', 'skill', 'type', 'title', 'order', 'duration_minutes', 'instructions', 'total_questions', 'navigation_enabled'])]
class ExamSection extends Model
{
    protected function casts(): array
    {
        return [
            'type' => SectionType::class,
            'navigation_enabled' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function questionGroups(): HasMany
    {
        return $this->hasMany(QuestionGroup::class);
    }
}
