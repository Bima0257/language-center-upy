<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use App\Rules\Turnstile;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.Pengguna::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'cf-turnstile-response' => [app()->environment('local', 'testing') ? 'sometimes' : 'required', new Turnstile],
        ]);

        $pengguna = DB::transaction(function () use ($request) {
            $pengguna = Pengguna::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $pengguna->assignRole('student');

            return $pengguna;
        });

        event(new Registered($pengguna));

        Auth::login($pengguna);

        return redirect(route('verification.notice', absolute: false));
    }
}
