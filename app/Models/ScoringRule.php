<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoringRule extends Model
{
    protected $fillable = [
        'exam_type_id',
        'section_skill',
        'conversion_table',
        'scoring_method',
        'rubric',
        'max_raw',
        'max_scaled',
    ];

    protected function casts(): array
    {
        return [
            'conversion_table' => 'array',
            'rubric' => 'array',
            'max_raw' => 'integer',
            'max_scaled' => 'integer',
        ];
    }

    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }
}
