<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Users\Services\GoogleAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function __construct(
        private GoogleAuthService $auth,
    ) {}

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

            $user = $this->auth->findByEmail($googleUser->getEmail());

            if ($action === 'register' && $user) {
                return redirect()->route('login')
                    ->with('error', 'Akun sudah terdaftar dengan email ini. Silakan login.');
            }

            if ($user) {
                if (! $user->is_active) {
                    return redirect()->route('login')
                        ->with('error', 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.');
                }

                $this->auth->syncGoogleIdentity($user, $googleUser->getId(), $googleUser->getAvatar());
            } else {
                $user = $this->auth->createGoogleUser(
                    $googleUser->getName(),
                    $googleUser->getEmail(),
                    $googleUser->getId(),
                    $googleUser->getAvatar(),
                );
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
