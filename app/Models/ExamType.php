<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property int $max_strikes
 * @property string|null $description
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Exam> $exams
 * @property-read Collection<int, QuestionBank> $questionBanks
 * @property-read Collection<int, ScoringRule> $scoringRules
 * @property-read Collection<int, ScoreInterpretation> $scoreInterpretations
 */
class ExamType extends Model
{
    protected $fillable = [
        'name',
        'max_strikes',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_strikes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function questionBanks(): HasMany
    {
        return $this->hasMany(QuestionBank::class);
    }

    public function scoringRules(): HasMany
    {
        return $this->hasMany(ScoringRule::class);
    }

    public function scoreInterpretations(): HasMany
    {
        return $this->hasMany(ScoreInterpretation::class);
    }
}
