<?php

namespace App\Modules\Ujian\Providers;

use App\Modules\Ujian\Repositories\BagianUjianRepository;
use App\Modules\Ujian\Repositories\BankSoalRepository;
use App\Modules\Ujian\Repositories\Contracts\BagianUjianRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\BankSoalRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\KebijakanMateriSoalRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\MateriSoalRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\MediaMateriSoalRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\SoalRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\UjianRepositoryInterface;
use App\Modules\Ujian\Repositories\KebijakanMateriSoalRepository;
use App\Modules\Ujian\Repositories\MateriSoalRepository;
use App\Modules\Ujian\Repositories\MediaMateriSoalRepository;
use App\Modules\Ujian\Repositories\SoalRepository;
use App\Modules\Ujian\Repositories\UjianRepository;
use Illuminate\Support\ServiceProvider;

class UjianModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UjianRepositoryInterface::class, UjianRepository::class);
        $this->app->bind(SoalRepositoryInterface::class, SoalRepository::class);
        $this->app->bind(BankSoalRepositoryInterface::class, BankSoalRepository::class);
        $this->app->bind(MateriSoalRepositoryInterface::class, MateriSoalRepository::class);
        $this->app->bind(MediaMateriSoalRepositoryInterface::class, MediaMateriSoalRepository::class);
        $this->app->bind(KebijakanMateriSoalRepositoryInterface::class, KebijakanMateriSoalRepository::class);
        $this->app->bind(BagianUjianRepositoryInterface::class, BagianUjianRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
