<?php

use App\Modules\Schedule\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin,superadmin'])
    ->prefix('admin/exams/{exam}/schedules')
    ->name('admin.schedules.')
    ->group(function () {
        Route::get('/', [ScheduleController::class, 'index'])->name('index');
        Route::get('/create', [ScheduleController::class, 'create'])->name('create');
        Route::post('/', [ScheduleController::class, 'store'])->name('store');
        Route::delete('/{schedule}', [ScheduleController::class, 'destroy'])->name('destroy');
    });
