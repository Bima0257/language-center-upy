<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $nim
 * @property int $faculty_id
 * @property int $department_id
 * @property int|null $batch_year
 * @property string|null $identity_photo
 * @property bool $is_verified
 * @property Carbon|null $verified_at
 * @property int|null $verified_by
 * @property string|null $verification_note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Faculty $faculty
 * @property-read Department $department
 * @property-read User|null $verifier
 */
class StudentProfile extends Model
{
    protected $fillable = [
        'user_id',
        'nim',
        'faculty_id',
        'department_id',
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

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
