<?php

namespace App\Modules\Session\Providers;

use App\Modules\Session\Repositories\AnswerRepository;
use App\Modules\Session\Repositories\Contracts\AnswerRepositoryInterface;
use App\Modules\Session\Repositories\Contracts\ExamSessionRepositoryInterface;
use App\Modules\Session\Repositories\ExamSessionRepository;
use Illuminate\Support\ServiceProvider;

class SessionModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ExamSessionRepositoryInterface::class, ExamSessionRepository::class);
        $this->app->bind(AnswerRepositoryInterface::class, AnswerRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
