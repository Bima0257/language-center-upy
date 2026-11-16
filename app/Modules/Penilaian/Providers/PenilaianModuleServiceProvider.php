<?php

namespace App\Modules\Penilaian\Providers;

use App\Modules\Penilaian\Repositories\AturanPenilaianRepository;
use App\Modules\Penilaian\Repositories\Contracts\AturanPenilaianRepositoryInterface;
use App\Modules\Penilaian\Repositories\Contracts\HasilPenilaianRepositoryInterface;
use App\Modules\Penilaian\Repositories\Contracts\SkemaPenilaianRepositoryInterface;
use App\Modules\Penilaian\Repositories\HasilPenilaianRepository;
use App\Modules\Penilaian\Repositories\SkemaPenilaianRepository;
use App\Modules\Penilaian\Services\LayananKonversiSkor;
use Illuminate\Support\ServiceProvider;

class PenilaianModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AturanPenilaianRepositoryInterface::class, AturanPenilaianRepository::class);
        $this->app->bind(SkemaPenilaianRepositoryInterface::class, SkemaPenilaianRepository::class);
        $this->app->bind(HasilPenilaianRepositoryInterface::class, HasilPenilaianRepository::class);
        $this->app->singleton(LayananKonversiSkor::class);
    }

    public function boot(): void
    {
        //
    }
}
