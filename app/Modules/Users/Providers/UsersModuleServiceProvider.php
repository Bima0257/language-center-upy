<?php

namespace App\Modules\Users\Providers;

use App\Modules\Users\Repositories\Contracts\UserRepositoryInterface;
use App\Modules\Users\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class UsersModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
