<?php

use App\Modules\Session\Controllers\ExamSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:student'])
    ->prefix('exam')
    ->name('exam.')
    ->group(function () {
        Route::get('/available', [ExamSessionController::class, 'available'])->name('available');
        Route::get('/{examSchedule}/pre-check', [ExamSessionController::class, 'preCheck'])->name('pre-check');
        Route::post('/{examSchedule}/start', [ExamSessionController::class, 'start'])->name('start');

        Route::middleware('exam.session.active')->group(function () {
            Route::get('/session/{examSession}/{section?}', [ExamSessionController::class, 'take'])->name('take');
            Route::post('/session/{examSession}/answer', [ExamSessionController::class, 'saveAnswer'])->name('save-answer');
            Route::post('/session/{examSession}/flag', [ExamSessionController::class, 'toggleFlag'])->name('toggle-flag');
            Route::post('/session/{examSession}/section-complete', [ExamSessionController::class, 'completeSection'])->name('section-complete');
            Route::post('/session/{examSession}/submit', [ExamSessionController::class, 'submit'])->name('submit');
            Route::post('/session/{examSession}/heartbeat', [ExamSessionController::class, 'heartbeat'])->name('heartbeat');
            Route::post('/session/{examSession}/violation', [ExamSessionController::class, 'logViolation'])->name('log-violation');
        });
    });
