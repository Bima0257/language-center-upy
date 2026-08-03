<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['exam_id', 'skill_id', 'title', 'order', 'duration_minutes', 'instructions', 'total_questions'])]
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
}
