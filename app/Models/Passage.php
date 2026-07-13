<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Passage extends Model
{
    protected $fillable = [
        'title',
        'content_text',
        'audio_url',
        'image_url',
        'language',
        'word_count',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'word_count' => 'integer',
        ];
    }

    public function questionGroups(): HasMany
    {
        return $this->hasMany(QuestionGroup::class);
    }
}
