<?php

namespace App\Modules\Users\Services;

use App\Models\User;
use App\Modules\Users\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GoogleAuthService
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {}

    public function findByEmail(string $email): ?User
    {
        return $this->users->findByEmail($email);
    }

    public function syncGoogleIdentity(User $user, string $googleId, ?string $googleAvatar): void
    {
        $this->users->update($user, [
            'google_id' => $googleId,
            'google_avatar' => $googleAvatar,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ]);
    }

    public function createGoogleUser(string $name, string $email, string $googleId, ?string $googleAvatar): User
    {
        return DB::transaction(function () use ($name, $email, $googleId, $googleAvatar) {
            $user = $this->users->create([
                'name' => $name,
                'email' => $email,
                'google_id' => $googleId,
                'google_avatar' => $googleAvatar,
                'password' => bcrypt(Str::random(32)),
                'email_verified_at' => now(),
            ]);

            $user->assignRole('student');

            return $user;
        });
    }
}
