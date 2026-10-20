<?php

namespace App\Modules\PelaksanaanUjian\Providers;

use App\Modules\PelaksanaanUjian\Repositories\Contracts\JawabanRepositoryInterface;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiUjianRepositoryInterface;
use App\Modules\PelaksanaanUjian\Repositories\JawabanRepository;
use App\Modules\PelaksanaanUjian\Repositories\SesiUjianRepository;
use Illuminate\Support\ServiceProvider;

class PelaksanaanUjianModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SesiUjianRepositoryInterface::class, SesiUjianRepository::class);
        $this->app->bind(JawabanRepositoryInterface::class, JawabanRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
