<?php

namespace App\Modules\Pengguna\Providers;

use App\Modules\Pengguna\Repositories\Contracts\PenggunaRepositoryInterface;
use App\Modules\Pengguna\Repositories\PenggunaRepository;
use Illuminate\Support\ServiceProvider;

class PenggunaModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PenggunaRepositoryInterface::class, PenggunaRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
