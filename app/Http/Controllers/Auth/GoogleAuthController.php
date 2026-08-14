<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $action = $request->query('action', 'login');

        if (! in_array($action, ['login', 'register'])) {
            $action = 'login';
        }

        session(['google_auth_action' => $action]);
        session(['google_auth_remember' => $request->boolean('remember')]);

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! $request->has('code') || $request->has('error')) {
            return redirect()->route('login')
                ->with('error', 'Login dengan Google dibatalkan.');
        }

        $action = session('google_auth_action', 'login');
        $remember = session('google_auth_remember', false);
        session()->forget(['google_auth_action', 'google_auth_remember']);

        try {
            $googleUser = Socialite::driver('google')->user();

            $user = User::where('email', $googleUser->getEmail())->first();

            if ($action === 'register' && $user) {
                return redirect()->route('login')
                    ->with('error', 'Akun sudah terdaftar dengan email ini. Silakan login.');
            }

            if ($user) {
                if (! $user->is_active) {
                    return redirect()->route('login')
                        ->with('error', 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.');
                }

                $user->google_id = $googleUser->getId();
                $user->google_avatar = $googleUser->getAvatar();
                if (! $user->email_verified_at) {
                    $user->email_verified_at = now();
                }
                $user->save();
            } else {
                $user = DB::transaction(function () use ($googleUser) {
                    $user = User::create([
                        'name' => $googleUser->getName(),
                        'email' => $googleUser->getEmail(),
                        'google_id' => $googleUser->getId(),
                        'google_avatar' => $googleUser->getAvatar(),
                        'password' => bcrypt(Str::random(32)),
                    ]);
                    $user->email_verified_at = now();
                    $user->save();
                    $user->assignRole('student');

                    return $user;
                });
            }
        } catch (\Throwable $e) {
            Log::warning('Google login gagal', ['error' => $e->getMessage()]);

            return redirect()->route('login')
                ->with('error', 'Login dengan Google gagal. Silakan coba lagi.');
        }

        Auth::login($user, $remember);

        $request->session()->regenerate();

        $user->invalidateOtherSessions();

        cookie()->queue(cookie('_logged', '1', config('session.lifetime')));

        if ($user->wasRecentlyCreated) {
            return redirect()->route('google.set-password');
        }

        if ($user->hasRole('student') && ! $user->hasVerifiedProfile()) {
            return redirect()->route('onboarding.verify-identity');
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

        $user->invalidateOtherSessions();

        cookie()->queue(cookie('_logged', '1', config('session.lifetime')));

        if ($user->hasRole('student') && ! $user->hasVerifiedProfile()) {
            return redirect()->route('onboarding.verify-identity');
        }

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Password berhasil dibuat. Sekarang Anda bisa login dengan email dan password.');
    }
}
