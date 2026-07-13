<?php

use App\Modules\Exam\Providers\ExamModuleServiceProvider;
use App\Modules\Schedule\Providers\ScheduleModuleServiceProvider;
use App\Modules\Scoring\Providers\ScoringModuleServiceProvider;
use App\Modules\Security\Providers\SecurityModuleServiceProvider;
use App\Modules\Session\Providers\SessionModuleServiceProvider;
use App\Modules\Proctor\Providers\ProctorModuleServiceProvider;
use App\Modules\Report\Providers\ReportModuleServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    ExamModuleServiceProvider::class,
    ScheduleModuleServiceProvider::class,
    SessionModuleServiceProvider::class,
    SecurityModuleServiceProvider::class,
    ProctorModuleServiceProvider::class,
    ScoringModuleServiceProvider::class,
    ReportModuleServiceProvider::class,
];
