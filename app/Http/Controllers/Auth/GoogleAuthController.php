<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Pengguna\Services\LayananAutentikasiGoogle;
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
        private LayananAutentikasiGoogle $layananAutentikasiGoogle,
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

            $pengguna = $this->layananAutentikasiGoogle->temukanDenganEmail($googleUser->getEmail());

            if ($action === 'register' && $pengguna) {
                return redirect()->route('login')
                    ->with('error', 'Akun sudah terdaftar dengan email ini. Silakan login.');
            }

            if ($pengguna) {
                if (! $pengguna->aktif) {
                    return redirect()->route('login')
                        ->with('error', 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.');
                }

                $this->layananAutentikasiGoogle->sinkronkanIdentitasGoogle($pengguna, $googleUser->getId(), $googleUser->getAvatar());
            } else {
                $pengguna = $this->layananAutentikasiGoogle->buatPenggunaGoogle(
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

        Auth::login($pengguna, $remember);

        $request->session()->regenerate();

        $pengguna->batalkanSesiLain();

        cookie()->queue(cookie('_logged', '1', config('session.lifetime')));

        if ($pengguna->wasRecentlyCreated) {
            return redirect()->route('google.set-password');
        }

        if ($pengguna->hasRole('student') && ! $pengguna->profilTerverifikasi()) {
            return redirect()->route('onboarding.verify-identity');
        }

        return redirect()->intended(route('dasbor'));
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

        $pengguna = auth()->user();
        $pengguna->password = Hash::make($request->password);
        $pengguna->save();

        $pengguna->batalkanSesiLain();

        cookie()->queue(cookie('_logged', '1', config('session.lifetime')));

        if ($pengguna->hasRole('student') && ! $pengguna->profilTerverifikasi()) {
            return redirect()->route('onboarding.verify-identity');
        }

        return redirect()->intended(route('dasbor'))
            ->with('success', 'Password berhasil dibuat. Sekarang Anda bisa login dengan email dan password.');
    }
}
