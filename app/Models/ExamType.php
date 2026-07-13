<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamType extends Model
{
    protected $fillable = [
        'name',
        'target_language',
        'section_config',
        'max_strikes',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'section_config' => 'array',
            'max_strikes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function scoringRules(): HasMany
    {
        return $this->hasMany(ScoringRule::class);
    }

    public function testForms(): HasMany
    {
        return $this->hasMany(TestForm::class);
    }
}
