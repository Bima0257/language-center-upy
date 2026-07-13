<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\StudentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        session(['google_auth_action' => $request->query('action', 'login')]);

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! $request->has('code') || $request->has('error')) {
            return redirect()->route('login')
                ->with('error', 'Login dengan Google dibatalkan.');
        }

        $action = session('google_auth_action', 'login');
        session()->forget('google_auth_action');

        $googleUser = Socialite::driver('google')->user();

        $user = User::where('email', $googleUser->email)->first();

        if ($action === 'register' && $user) {
            return redirect()->route('login')
                ->with('error', 'Akun sudah terdaftar dengan email ini. Silakan login.');
        }

        if ($user) {
            if (! $user->is_active) {
                return redirect()->route('login')
                    ->with('error', 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.');
            }

            $user->google_id = $googleUser->id;
            $user->google_avatar = $googleUser->avatar;
            if (! $user->email_verified_at) {
                $user->email_verified_at = now();
            }
            $user->save();
        } else {
            $user = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'google_id' => $googleUser->id,
                'google_avatar' => $googleUser->avatar,
                'password' => bcrypt(Str::random(32)),
            ]);
            $user->email_verified_at = now();
            $user->save();
            $user->assignRole('student');

            StudentProfile::create(['user_id' => $user->id]);
        }

        Auth::login($user);

        if ($user->wasRecentlyCreated) {
            return redirect()->route('google.set-password');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function showSetPassword(): Response
    {
        return Inertia::render('Auth/SetPassword');
    }

    public function storeSetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = auth()->user();
        $user->password = Hash::make($request->password);
        $user->save();

        Auth::logoutOtherDevices($request->password);

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Password berhasil dibuat. Sekarang Anda bisa login dengan email dan password.');
    }
}
