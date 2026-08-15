<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $exam_section_id
 * @property int $skill_part_id
 * @property int $order
 * @property-read ExamSection $examSection
 * @property-read SkillPart $skillPart
 */
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
