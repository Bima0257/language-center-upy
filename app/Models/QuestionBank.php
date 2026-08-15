<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $exam_type_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property-read ExamType|null $examType
 * @property-read Collection<int, Question> $questions
 * @property-read Collection<int, SkillPart> $skillParts
 */
class QuestionBank extends Model
{
    protected $fillable = [
        'exam_type_id',
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function skillParts(): HasMany
    {
        return $this->hasMany(SkillPart::class);
    }
}
