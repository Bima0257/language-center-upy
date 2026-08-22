<?php

namespace App\Modules\MasterData\Providers;

use App\Modules\MasterData\Repositories\CertificateRepository;
use App\Modules\MasterData\Repositories\Contracts\CertificateRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\ExamTypeRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\FacultyRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\ScoreInterpretationRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\SkillPartRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\SkillRepositoryInterface;
use App\Modules\MasterData\Repositories\DepartmentRepository;
use App\Modules\MasterData\Repositories\ExamTypeRepository;
use App\Modules\MasterData\Repositories\FacultyRepository;
use App\Modules\MasterData\Repositories\ScoreInterpretationRepository;
use App\Modules\MasterData\Repositories\SkillPartRepository;
use App\Modules\MasterData\Repositories\SkillRepository;
use Illuminate\Support\ServiceProvider;

class MasterDataModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ExamTypeRepositoryInterface::class, ExamTypeRepository::class);
        $this->app->bind(SkillRepositoryInterface::class, SkillRepository::class);
        $this->app->bind(SkillPartRepositoryInterface::class, SkillPartRepository::class);
        $this->app->bind(FacultyRepositoryInterface::class, FacultyRepository::class);
        $this->app->bind(DepartmentRepositoryInterface::class, DepartmentRepository::class);
        $this->app->bind(ScoreInterpretationRepositoryInterface::class, ScoreInterpretationRepository::class);
        $this->app->bind(CertificateRepositoryInterface::class, CertificateRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
