<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoreInterpretation extends Model
{
    protected $fillable = [
        'exam_type_id',
        'min_score',
        'max_score',
        'cefr_level',
        'level_label',
        'is_passing',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'min_score' => 'integer',
            'max_score' => 'integer',
            'is_passing' => 'boolean',
        ];
    }

    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }
}
