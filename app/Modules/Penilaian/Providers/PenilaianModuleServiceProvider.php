<?php

namespace App\Modules\Penilaian\Providers;

use App\Modules\Penilaian\Services\LayananKonversiSkor;
use Illuminate\Support\ServiceProvider;

class PenilaianModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LayananKonversiSkor::class);
    }

    public function boot(): void
    {
        //
    }
}
