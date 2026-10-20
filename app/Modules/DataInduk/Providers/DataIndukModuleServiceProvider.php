<?php

namespace App\Modules\DataInduk\Providers;

use App\Modules\DataInduk\Repositories\BagianSoalRepository;
use App\Modules\DataInduk\Repositories\Contracts\BagianSoalRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\FakultasRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\InterpretasiSkorRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\JenisTesRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\KeterampilanRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\ProgramStudiRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\SertifikatRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\UrutanKodeRepositoryInterface;
use App\Modules\DataInduk\Repositories\FakultasRepository;
use App\Modules\DataInduk\Repositories\InterpretasiSkorRepository;
use App\Modules\DataInduk\Repositories\JenisTesRepository;
use App\Modules\DataInduk\Repositories\KeterampilanRepository;
use App\Modules\DataInduk\Repositories\ProgramStudiRepository;
use App\Modules\DataInduk\Repositories\SertifikatRepository;
use App\Modules\DataInduk\Repositories\UrutanKodeRepository;
use Illuminate\Support\ServiceProvider;

class DataIndukModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(JenisTesRepositoryInterface::class, JenisTesRepository::class);
        $this->app->bind(KeterampilanRepositoryInterface::class, KeterampilanRepository::class);
        $this->app->bind(BagianSoalRepositoryInterface::class, BagianSoalRepository::class);
        $this->app->bind(FakultasRepositoryInterface::class, FakultasRepository::class);
        $this->app->bind(ProgramStudiRepositoryInterface::class, ProgramStudiRepository::class);
        $this->app->bind(InterpretasiSkorRepositoryInterface::class, InterpretasiSkorRepository::class);
        $this->app->bind(SertifikatRepositoryInterface::class, SertifikatRepository::class);
        $this->app->bind(UrutanKodeRepositoryInterface::class, UrutanKodeRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
