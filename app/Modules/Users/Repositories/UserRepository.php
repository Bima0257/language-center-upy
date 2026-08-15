<?php

namespace App\Modules\Users\Repositories;

use App\Models\StudentProfile;
use App\Models\User;
use App\Modules\Users\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    public function count(): int
    {
        return User::count();
    }

    public function pendingVerificationUsers(): Collection
    {
        return User::role('student')
            ->whereHas('studentProfile', fn ($q) => $q->whereNotNull('identity_photo'))
            ->with(['studentProfile.faculty', 'studentProfile.department'])
            ->select(['id', 'name', 'email', 'photo', 'created_at'])
            ->get();
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function createStudentProfile(User $user, array $data): StudentProfile
    {
        return StudentProfile::create([...$data, 'user_id' => $user->id]);
    }

    public function updateStudentProfile(User $user, array $data): StudentProfile
    {
        $profile = $user->studentProfile;

        if ($profile === null) {
            return $this->createStudentProfile($user, $data);
        }

        $profile->update($data);

        return $profile->fresh();
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh();
    }
}
