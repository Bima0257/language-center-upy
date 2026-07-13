<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['exam_session_id', 'type', 'severity', 'description', 'metadata', 'strike_count', 'screenshot_path'])]
class ViolationLog extends Model
{
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'strike_count' => 'integer',
        ];
    }

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class);
    }
}
