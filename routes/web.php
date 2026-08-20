<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CertificateController;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\ScoreInterpretationController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware('auth')->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/verify-identity', [OnboardingController::class, 'verifyIdentity'])->name('verify-identity');
    Route::post('/upload-identity', [OnboardingController::class, 'uploadIdentity'])->name('upload-identity');
});

Route::middleware(['auth', 'verified', 'verified.user'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:admin,superadmin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/verify-users', [VerificationController::class, 'index'])->name('verify-users');
        Route::post('/verify-users/{user}/approve', [VerificationController::class, 'approve'])->name('verify-users.approve');
        Route::post('/verify-users/{user}/reject', [VerificationController::class, 'reject'])->name('verify-users.reject');
        Route::post('/verify-users/{user}/revert', [VerificationController::class, 'revert'])->name('verify-users.revert');

        Route::get('/master-data/exam-types', [MasterDataController::class, 'examTypesIndex'])->name('master-data.exam-types.index');
        Route::post('/master-data/exam-types', [MasterDataController::class, 'examTypeStore'])->name('master-data.exam-types.store');
        Route::put('/master-data/exam-types/{examType}', [MasterDataController::class, 'examTypeUpdate'])->name('master-data.exam-types.update');
        Route::delete('/master-data/exam-types/{examType}', [MasterDataController::class, 'examTypeDestroy'])->name('master-data.exam-types.destroy');

        Route::get('/master-data/parts', [MasterDataController::class, 'partsIndex'])->name('master-data.parts.index');
        Route::post('/master-data/parts', [MasterDataController::class, 'partStore'])->name('master-data.parts.store');
        Route::post('/master-data/parts/reorder', [MasterDataController::class, 'partReorder'])->name('master-data.parts.reorder');
        Route::put('/master-data/parts/{skillPart}', [MasterDataController::class, 'partUpdate'])->name('master-data.parts.update');
        Route::delete('/master-data/parts/{skillPart}', [MasterDataController::class, 'partDestroy'])->name('master-data.parts.destroy');

        Route::get('/master-data/faculties', [MasterDataController::class, 'facultiesIndex'])->name('master-data.faculties.index');
        Route::post('/master-data/faculties', [MasterDataController::class, 'facultyStore'])->name('master-data.faculties.store');
        Route::put('/master-data/faculties/{faculty}', [MasterDataController::class, 'facultyUpdate'])->name('master-data.faculties.update');
        Route::delete('/master-data/faculties/{faculty}', [MasterDataController::class, 'facultyDestroy'])->name('master-data.faculties.destroy');

        Route::get('/master-data/departments', [MasterDataController::class, 'departmentsIndex'])->name('master-data.departments.index');
        Route::post('/master-data/departments', [MasterDataController::class, 'departmentStore'])->name('master-data.departments.store');
        Route::put('/master-data/departments/{department}', [MasterDataController::class, 'departmentUpdate'])->name('master-data.departments.update');
        Route::delete('/master-data/departments/{department}', [MasterDataController::class, 'departmentDestroy'])->name('master-data.departments.destroy');

        Route::get('/master-data/score-interpretations', [ScoreInterpretationController::class, 'index'])->name('master-data.score-interpretations.index');
        Route::post('/master-data/score-interpretations', [ScoreInterpretationController::class, 'store'])->name('master-data.score-interpretations.store');
        Route::put('/master-data/score-interpretations/{scoreInterpretation}', [ScoreInterpretationController::class, 'update'])->name('master-data.score-interpretations.update');
        Route::delete('/master-data/score-interpretations/{scoreInterpretation}', [ScoreInterpretationController::class, 'destroy'])->name('master-data.score-interpretations.destroy');

        Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
        Route::post('/certificates', [CertificateController::class, 'store'])->name('certificates.store');
        Route::delete('/certificates/{certificate}', [CertificateController::class, 'destroy'])->name('certificates.destroy');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::patch('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::patch('/settings', [SettingController::class, 'update'])->name('settings.update');

        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Load module routes
    foreach (glob(app_path('Modules/*/routes.php')) as $moduleRoute) {
        require $moduleRoute;
    }
});

require __DIR__.'/auth.php';
