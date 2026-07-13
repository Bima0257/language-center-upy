<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'type'])]
class Tag extends Model
{
    protected function casts(): array
    {
        return [
            'type' => 'string',
        ];
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'question_tag');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
