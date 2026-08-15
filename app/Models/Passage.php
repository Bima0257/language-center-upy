<?php

namespace App\Models;

use App\Enums\PassageType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property PassageType $type
 * @property string|null $content_text
 * @property string|null $audio_url
 * @property string|null $image_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Question> $questions
 */
class Passage extends Model
{
    protected $fillable = [
        'title',
        'type',
        'content_text',
        'audio_url',
        'image_url',
    ];

    protected function casts(): array
    {
        return [
            'type' => PassageType::class,
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
