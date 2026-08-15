<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $phone
 * @property string|null $photo
 * @property bool $is_active
 * @property string|null $google_id
 * @property string|null $google_avatar
 * @property string|null $remember_token
 * @property bool $is_verified
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read StudentProfile|null $studentProfile
 * @property-read Collection<int, ExamSession> $examSessions
 */
#[Fillable(['name', 'email', 'password', 'phone', 'photo', 'is_active', 'google_id', 'google_avatar', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $appends = ['is_verified'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function examSessions(): HasMany
    {
        return $this->hasMany(ExamSession::class);
    }

    public function hasVerifiedProfile(): bool
    {
        if (! $this->isStudent()) {
            return true;
        }

        $profile = $this->studentProfile;

        return $profile !== null ? $profile->is_verified : false;
    }

    public function getIsVerifiedAttribute(): bool
    {
        return $this->hasVerifiedProfile();
    }

    public function isStudent(): bool
    {
        return $this->hasRole('student');
    }

    public function invalidateOtherSessions(): void
    {
        DB::table('sessions')
            ->where('user_id', $this->id)
            ->where('id', '!=', session()->getId())
            ->delete();
    }
}
