<?php

namespace App\Modules\Security\Providers;

use App\Modules\Security\Repositories\Contracts\ViolationRepositoryInterface;
use App\Modules\Security\Repositories\ViolationRepository;
use App\Modules\Security\Services\SecurityService;
use Illuminate\Support\ServiceProvider;

class SecurityModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ViolationRepositoryInterface::class, ViolationRepository::class);
        $this->app->singleton(SecurityService::class);
    }

    public function boot(): void
    {
        //
    }
}
