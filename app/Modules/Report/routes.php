<?php

use App\Modules\Report\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin,superadmin'])
    ->prefix('admin/reports')
    ->name('admin.reports.')
    ->group(function () {
        Route::get('/integrity', [ReportController::class, 'integrity'])->name('integrity');
        Route::get('/integrity/export/{examSession}', [ReportController::class, 'exportIntegrity'])->name('integrity.export');
    });
