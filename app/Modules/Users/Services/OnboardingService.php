<?php

namespace App\Modules\Users\Services;

use App\Models\User;
use App\Modules\MasterData\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\FacultyRepositoryInterface;
use App\Modules\Users\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class OnboardingService
{
    public function __construct(
        private UserRepositoryInterface $users,
        private FacultyRepositoryInterface $faculties,
        private DepartmentRepositoryInterface $departments,
    ) {}

    public function verifyIdentityData(User $user): array
    {
        $profile = $user->studentProfile;

        return [
            'user' => $user,
            'faculties' => $this->faculties->allActiveOrdered(),
            'departments' => $this->departments->allActiveOrdered(),
            'hasUploaded' => $profile !== null && $profile->identity_photo !== null && $profile->nim,
            'profile' => $profile ? [
                'nim' => $profile->nim,
                'faculty_id' => $profile->faculty_id,
                'department_id' => $profile->department_id,
                'batch_year' => $profile->batch_year,
            ] : null,
        ];
    }

    public function uploadIdentity(
        User $user,
        array $data,
        ?UploadedFile $identityPhoto,
        ?UploadedFile $photo,
    ): void {
        $profile = $user->studentProfile;

        $profileData = [
            'nim' => $data['nim'],
            'faculty_id' => $data['faculty_id'],
            'department_id' => $data['department_id'],
            'batch_year' => $data['batch_year'],
        ];

        if ($identityPhoto !== null) {
            if ($profile !== null && $profile->identity_photo) {
                Storage::disk('public')->delete($profile->identity_photo);
            }

            $profileData['identity_photo'] = $identityPhoto->store('identities', 'public');
        }

        $this->users->updateStudentProfile($user, $profileData);

        if ($photo !== null) {
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }

            $this->users->update($user, ['photo' => $photo->store('photos', 'public')]);
        }
    }
}
