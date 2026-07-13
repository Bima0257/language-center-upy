<?php

namespace App\Modules\Scoring\Providers;

use App\Modules\Scoring\Services\ScoreConversionService;
use Illuminate\Support\ServiceProvider;

class ScoringModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ScoreConversionService::class);
    }

    public function boot(): void
    {
        //
    }
}
