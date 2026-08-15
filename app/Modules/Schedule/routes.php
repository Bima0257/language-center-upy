<?php

use App\Modules\Schedule\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin,superadmin'])
    ->prefix('admin/schedules')
    ->name('admin.schedules.')
    ->group(function () {
        Route::get('/', [ScheduleController::class, 'all'])->name('all');
        Route::get('/{schedule}', [ScheduleController::class, 'show'])->name('show');
        Route::get('/{schedule}/edit', [ScheduleController::class, 'edit'])->name('edit');
        Route::put('/{schedule}', [ScheduleController::class, 'update'])->name('update');
        Route::delete('/{schedule}', [ScheduleController::class, 'destroy'])->name('destroy');

        Route::post('/{schedule}/slots', [ScheduleController::class, 'storeSlot'])->name('slots.store');
        Route::put('/slots/{slot}', [ScheduleController::class, 'updateSlot'])->name('slots.update');
        Route::delete('/slots/{slot}', [ScheduleController::class, 'destroySlot'])->name('slots.destroy');
    });

Route::middleware(['auth', 'role:admin,superadmin'])
    ->prefix('admin/exams/{exam}/schedules')
    ->name('admin.schedules.')
    ->group(function () {
        Route::get('/create', [ScheduleController::class, 'create'])->name('create');
        Route::post('/', [ScheduleController::class, 'store'])->name('store');
    });
