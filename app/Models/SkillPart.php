<?php

namespace App\Models;

use App\Enums\SkillCode;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $question_bank_id
 * @property SkillCode $skill
 * @property string $name
 * @property int $order
 * @property string|null $directions
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read QuestionBank $questionBank
 * @property-read Collection<int, Question> $questions
 */
class SkillPart extends Model
{
    protected $fillable = [
        'question_bank_id',
        'skill',
        'name',
        'order',
        'directions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'skill' => SkillCode::class,
            'order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function questionBank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
