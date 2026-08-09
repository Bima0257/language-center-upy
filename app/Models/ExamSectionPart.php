<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSectionPart extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'exam_section_id',
        'skill_part_id',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    public function examSection(): BelongsTo
    {
        return $this->belongsTo(ExamSection::class);
    }

    public function skillPart(): BelongsTo
    {
        return $this->belongsTo(SkillPart::class);
    }
}
