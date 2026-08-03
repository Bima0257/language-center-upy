<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function verifyIdentity(): Response
    {
        $user = auth()->user();
        $profile = $user->studentProfile;
        $hasUploaded = $profile && $profile->identity_photo !== null && $profile->nim;

        return Inertia::render('Onboarding/IdentityVerification', [
            'user' => $user,
            'faculties' => Faculty::where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'hasUploaded' => $hasUploaded,
            'profile' => $profile ? [
                'nim' => $profile->nim,
                'faculty_id' => $profile->faculty_id,
                'department_id' => $profile->department_id,
                'batch_year' => $profile->batch_year,
            ] : null,
        ]);
    }

    public function uploadIdentity(Request $request): RedirectResponse
    {
        $request->validate([
            'nim' => 'required|string|max:20',
            'faculty_id' => 'required|exists:faculties,id',
            'department_id' => 'required|exists:departments,id',
            'batch_year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'identity_photo' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $user = auth()->user();
        $profile = $user->studentProfile;

        if (! $profile) {
            $profile = new StudentProfile(['user_id' => $user->id]);
        }

        $profile->nim = $request->nim;
        $profile->faculty_id = $request->faculty_id;
        $profile->department_id = $request->department_id;
        $profile->batch_year = $request->batch_year;

        if ($request->hasFile('identity_photo')) {
            $path = $request->file('identity_photo')->store('identities', 'public');
            $profile->identity_photo = $path;
        }

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('photos', 'public');
            $user->photo = $path;
        }

        $profile->save();
        $user->save();

        return redirect()->route('onboarding.verify-identity')->with('success', 'Identitas berhasil diunggah. Menunggu verifikasi admin.');
    }
}
