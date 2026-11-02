<?php

namespace App\Modules\PendaftaranUjian\Providers;

use App\Modules\PendaftaranUjian\Repositories\Contracts\PendaftaranUjianRepositoryInterface;
use App\Modules\PendaftaranUjian\Repositories\Contracts\PeriodePendaftaranUjianRepositoryInterface;
use App\Modules\PendaftaranUjian\Repositories\PendaftaranUjianRepository;
use App\Modules\PendaftaranUjian\Repositories\PeriodePendaftaranUjianRepository;
use Illuminate\Support\ServiceProvider;

class PendaftaranUjianModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PendaftaranUjianRepositoryInterface::class, PendaftaranUjianRepository::class);
        $this->app->bind(PeriodePendaftaranUjianRepositoryInterface::class, PeriodePendaftaranUjianRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
