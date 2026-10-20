<?php

namespace App\Modules\Keamanan\Providers;

use App\Modules\Keamanan\Repositories\Contracts\PelanggaranRepositoryInterface;
use App\Modules\Keamanan\Repositories\PelanggaranRepository;
use App\Modules\Keamanan\Services\LayananKeamanan;
use Illuminate\Support\ServiceProvider;

class KeamananModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PelanggaranRepositoryInterface::class, PelanggaranRepository::class);
        $this->app->singleton(LayananKeamanan::class);
    }

    public function boot(): void
    {
        //
    }
}
