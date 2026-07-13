<?php

use App\Modules\Proctor\Controllers\ProctorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:proctor,admin,superadmin'])
    ->prefix('proctor')
    ->name('proctor.')
    ->group(function () {
        Route::get('/dashboard', [ProctorController::class, 'dashboard'])->name('dashboard');
        Route::get('/sessions/{examSession}', [ProctorController::class, 'showSession'])->name('session.show');
        Route::get('/sessions/{examSession}/violations', [ProctorController::class, 'violations'])->name('session.violations');
        Route::post('/sessions/{examSession}/terminate', [ProctorController::class, 'terminate'])->name('session.terminate');
        Route::post('/sessions/{examSession}/review', [ProctorController::class, 'review'])->name('session.review');
        Route::get('/flagged', [ProctorController::class, 'flaggedSessions'])->name('flagged');
    });
