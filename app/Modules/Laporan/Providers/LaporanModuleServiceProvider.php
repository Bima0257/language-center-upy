<?php

namespace App\Modules\Laporan\Providers;

use App\Modules\Laporan\Repositories\Contracts\LaporanRepositoryInterface;
use App\Modules\Laporan\Repositories\LaporanRepository;
use Illuminate\Support\ServiceProvider;

class LaporanModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LaporanRepositoryInterface::class, LaporanRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
