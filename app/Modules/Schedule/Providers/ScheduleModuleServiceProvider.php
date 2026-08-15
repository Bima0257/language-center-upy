<?php

namespace App\Modules\Schedule\Providers;

use App\Modules\Schedule\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Modules\Schedule\Repositories\Contracts\SlotRepositoryInterface;
use App\Modules\Schedule\Repositories\ScheduleRepository;
use App\Modules\Schedule\Repositories\SlotRepository;
use Illuminate\Support\ServiceProvider;

class ScheduleModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ScheduleRepositoryInterface::class, ScheduleRepository::class);
        $this->app->bind(SlotRepositoryInterface::class, SlotRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
