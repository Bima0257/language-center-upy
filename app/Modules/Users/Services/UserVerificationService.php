<?php

namespace App\Modules\Users\Services;

use App\Models\User;
use App\Modules\Users\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class UserVerificationService
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {}

    public function indexData(): array
    {
        return [
            'users' => $this->users->pendingVerificationUsers(),
        ];
    }

    public function approve(User $user): void
    {
        $this->requireProfile($user);

        $this->users->updateStudentProfile($user, [
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => auth()->id(),
        ]);
    }

    public function revert(User $user): void
    {
        $this->requireProfile($user);

        $this->users->updateStudentProfile($user, [
            'is_verified' => false,
            'verified_at' => null,
            'verified_by' => null,
        ]);
    }

    public function reject(User $user, ?string $reason): void
    {
        $profile = $user->studentProfile;

        if ($profile !== null) {
            if ($profile->identity_photo) {
                Storage::disk('public')->delete($profile->identity_photo);
            }

            $this->users->updateStudentProfile($user, [
                'identity_photo' => null,
                'verification_note' => $reason,
            ]);
        }

        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
        }

        $this->users->update($user, ['photo' => null]);
    }

    private function requireProfile(User $user): void
    {
        if ($user->studentProfile === null) {
            throw new RuntimeException('Profile mahasiswa tidak ditemukan.');
        }
    }
}
