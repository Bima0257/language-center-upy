<?php

namespace App\Http\Controllers;

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
            'hasUploaded' => $hasUploaded,
            'profile' => $profile ? [
                'nim' => $profile->nim,
                'faculty' => $profile->faculty,
                'department' => $profile->department,
                'batch_year' => $profile->batch_year,
            ] : null,
        ]);
    }

    public function uploadIdentity(Request $request): RedirectResponse
    {
        $request->validate([
            'nim' => 'required|string|max:20',
            'faculty' => 'required|string|max:100',
            'department' => 'required|string|max:100',
            'batch_year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'identity_photo' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $user = auth()->user();
        $profile = $user->studentProfile ?? $user->studentProfile()->create([]);

        $profile->nim = $request->nim;
        $profile->faculty = $request->faculty;
        $profile->department = $request->department;
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
