<?php

namespace App\Modules\Identity\Providers;

use App\Modules\Identity\Contracts\IdentityContract;
use App\Modules\Identity\Services\IdentityService;
use Illuminate\Support\ServiceProvider;

class IdentityModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IdentityContract::class, IdentityService::class);
    }
}
