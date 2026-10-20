<?php

namespace App\Providers;

use App\Models\Pengguna;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DevCommands::except('logs');

        Vite::prefetch(concurrency: 3);

        Gate::before(function (Pengguna $pengguna, string $ability): ?bool {
            return $pengguna->hasRole('superadmin') ? true : null;
        });

        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols();
        });
    }
}
