<?php

namespace App\Modules\Exam\Providers;

use App\Modules\Exam\Repositories\Contracts\ExamRepositoryInterface;
use App\Modules\Exam\Repositories\ExamRepository;
use Illuminate\Support\ServiceProvider;

class ExamModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ExamRepositoryInterface::class, ExamRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
