<?php

namespace App\Modules\Report\Providers;

use App\Modules\Report\Repositories\Contracts\ReportRepositoryInterface;
use App\Modules\Report\Repositories\ReportRepository;
use Illuminate\Support\ServiceProvider;

class ReportModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ReportRepositoryInterface::class, ReportRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
