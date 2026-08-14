<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $exam_type_id
 * @property string $section_skill
 * @property array<string, int> $conversion_table
 * @property int $max_raw
 * @property int $max_scaled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ExamType $examType
 */
class ScoringRule extends Model
{
    protected $fillable = [
        'exam_type_id',
        'section_skill',
        'conversion_table',
        'max_raw',
        'max_scaled',
    ];

    protected function casts(): array
    {
        return [
            'conversion_table' => 'array',
            'max_raw' => 'integer',
            'max_scaled' => 'integer',
        ];
    }

    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }
}
