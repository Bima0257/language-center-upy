<?php

namespace App\Modules\DataInduk\Providers;

use App\Modules\DataInduk\Repositories\Contracts\FakultasRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\InterpretasiSkorRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\PartSoalRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\ProgramStudiRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\ProgramTesRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\SectionRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\SertifikatRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\UrutanKodeRepositoryInterface;
use App\Modules\DataInduk\Repositories\FakultasRepository;
use App\Modules\DataInduk\Repositories\InterpretasiSkorRepository;
use App\Modules\DataInduk\Repositories\PartSoalRepository;
use App\Modules\DataInduk\Repositories\ProgramStudiRepository;
use App\Modules\DataInduk\Repositories\ProgramTesRepository;
use App\Modules\DataInduk\Repositories\SectionRepository;
use App\Modules\DataInduk\Repositories\SertifikatRepository;
use App\Modules\DataInduk\Repositories\UrutanKodeRepository;
use Illuminate\Support\ServiceProvider;

class DataIndukModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProgramTesRepositoryInterface::class, ProgramTesRepository::class);
        $this->app->bind(SectionRepositoryInterface::class, SectionRepository::class);
        $this->app->bind(PartSoalRepositoryInterface::class, PartSoalRepository::class);
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
