<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestFormQuestion extends Model
{
    protected $fillable = [
        'test_form_id',
        'question_id',
        'order',
    ];

    public function testForm(): BelongsTo
    {
        return $this->belongsTo(TestForm::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
