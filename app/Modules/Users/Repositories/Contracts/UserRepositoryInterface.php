<?php

namespace App\Modules\Users\Repositories\Contracts;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    public function count(): int;

    public function pendingVerificationUsers(): Collection;

    public function findByEmail(string $email): ?User;

    public function create(array $data): User;

    public function createStudentProfile(User $user, array $data): StudentProfile;

    public function updateStudentProfile(User $user, array $data): StudentProfile;

    public function update(User $user, array $data): User;
}
