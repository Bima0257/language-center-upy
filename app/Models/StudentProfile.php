<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    protected $fillable = [
        'user_id',
        'nim',
        'faculty',
        'department',
        'batch_year',
        'identity_photo',
        'is_verified',
        'verified_at',
        'verified_by',
        'verification_note',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'batch_year' => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
