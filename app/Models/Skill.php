<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property int $order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, SkillPart> $skillParts
 * @property-read Collection<int, Question> $questions
 * @property-read Collection<int, ExamSection> $examSections
 */
class Skill extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function skillParts(): HasMany
    {
        return $this->hasMany(SkillPart::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function examSections(): HasMany
    {
        return $this->hasMany(ExamSection::class);
    }
}
