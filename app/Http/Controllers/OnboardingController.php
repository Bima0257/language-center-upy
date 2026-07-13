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
        return Inertia::render('Onboarding/IdentityVerification', [
            'user' => auth()->user(),
        ]);
    }

    public function uploadIdentity(Request $request): RedirectResponse
    {
        $request->validate([
            'identity_photo' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $user = auth()->user();
        $profile = $user->studentProfile ?? $user->studentProfile()->create([]);

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

        return redirect()->route('dashboard')->with('success', 'Identitas berhasil diunggah. Menunggu verifikasi admin.');
    }
}
