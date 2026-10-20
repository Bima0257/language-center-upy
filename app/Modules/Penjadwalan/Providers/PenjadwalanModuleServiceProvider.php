<?php

namespace App\Modules\Penjadwalan\Providers;

use App\Modules\Penjadwalan\Repositories\Contracts\PenjadwalanRepositoryInterface;
use App\Modules\Penjadwalan\Repositories\Contracts\SlotUjianRepositoryInterface;
use App\Modules\Penjadwalan\Repositories\PenjadwalanRepository;
use App\Modules\Penjadwalan\Repositories\SlotUjianRepository;
use Illuminate\Support\ServiceProvider;

class PenjadwalanModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PenjadwalanRepositoryInterface::class, PenjadwalanRepository::class);
        $this->app->bind(SlotUjianRepositoryInterface::class, SlotUjianRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
