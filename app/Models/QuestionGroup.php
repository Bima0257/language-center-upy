<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['exam_section_id', 'passage_id', 'title', 'group_type', 'passage_text', 'audio_file', 'image', 'topic', 'word_count', 'order'])]
class QuestionGroup extends Model
{
    protected function casts(): array
    {
        return [
            'word_count' => 'integer',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ExamSection::class, 'exam_section_id');
    }

    public function passage(): BelongsTo
    {
        return $this->belongsTo(Passage::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
