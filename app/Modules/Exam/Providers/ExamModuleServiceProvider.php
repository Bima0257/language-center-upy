<?php

namespace App\Modules\Exam\Providers;

use App\Modules\Exam\Repositories\Contracts\ExamRepositoryInterface;
use App\Modules\Exam\Repositories\Contracts\ExamSectionRepositoryInterface;
use App\Modules\Exam\Repositories\Contracts\PassageRepositoryInterface;
use App\Modules\Exam\Repositories\Contracts\QuestionBankRepositoryInterface;
use App\Modules\Exam\Repositories\Contracts\QuestionRepositoryInterface;
use App\Modules\Exam\Repositories\ExamRepository;
use App\Modules\Exam\Repositories\ExamSectionRepository;
use App\Modules\Exam\Repositories\PassageRepository;
use App\Modules\Exam\Repositories\QuestionBankRepository;
use App\Modules\Exam\Repositories\QuestionRepository;
use Illuminate\Support\ServiceProvider;

class ExamModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ExamRepositoryInterface::class, ExamRepository::class);
        $this->app->bind(QuestionRepositoryInterface::class, QuestionRepository::class);
        $this->app->bind(QuestionBankRepositoryInterface::class, QuestionBankRepository::class);
        $this->app->bind(PassageRepositoryInterface::class, PassageRepository::class);
        $this->app->bind(ExamSectionRepositoryInterface::class, ExamSectionRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
