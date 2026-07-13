<?php

namespace App\Models;

use App\Enums\ExamMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['exam_type_id', 'title', 'description', 'mode', 'duration_minutes', 'is_active'])]
class Exam extends Model
{
    protected function casts(): array
    {
        return [
            'mode' => ExamMode::class,
            'is_active' => 'boolean',
            'duration_minutes' => 'integer',
        ];
    }

    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ExamSection::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ExamSchedule::class);
    }
}
