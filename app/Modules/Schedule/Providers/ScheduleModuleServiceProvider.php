<?php

namespace App\Modules\Schedule\Providers;

use App\Modules\Schedule\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Modules\Schedule\Repositories\ScheduleRepository;
use Illuminate\Support\ServiceProvider;

class ScheduleModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ScheduleRepositoryInterface::class, ScheduleRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
