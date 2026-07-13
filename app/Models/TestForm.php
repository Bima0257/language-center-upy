<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestForm extends Model
{
    protected $fillable = [
        'exam_type_id',
        'exam_id',
        'name',
        'assembly_mode',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'assembly_mode' => 'string',
            'status' => 'string',
        ];
    }

    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(TestFormQuestion::class);
    }
}
