<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Skill extends Model
{
    public const SYSTEM_CODES = ['reading', 'listening'];

    protected $fillable = [
        'exam_type_id',
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $appends = ['is_system'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function isSystemSkill(): bool
    {
        return in_array($this->code, self::SYSTEM_CODES, true);
    }

    public function getIsSystemAttribute(): bool
    {
        return $this->isSystemSkill();
    }

    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ExamSection::class);
    }

    public function skillParts(): HasMany
    {
        return $this->hasMany(SkillPart::class)->orderBy('order');
    }
}
