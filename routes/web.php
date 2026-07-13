<?php

use App\Http\Controllers\Admin\VerificationController;
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

Route::get('/dashboard', function () {
    $user = auth()->user();
    $data = [];

    if ($user->hasRole('student')) {
        $data['recentSessions'] = \App\Models\ExamSession::with('schedule.exam')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();
        $data['availableExamsCount'] = \App\Models\ExamSchedule::where('is_active', true)
            ->where('scheduled_start', '<=', now())
            ->where('scheduled_end', '>=', now())
            ->count();
    }

    if ($user->hasRole('admin') || $user->hasRole('superadmin')) {
        $data['totalUsers'] = \App\Models\User::count();
        $data['totalExams'] = \App\Models\Exam::count();
        $data['activeSessionsCount'] = \App\Models\ExamSession::where('status', 'in_progress')->count();
        $data['flaggedSessionsCount'] = \App\Models\ExamSession::where('is_flagged', true)->whereNull('reviewed_at')->count();
    }

    if ($user->hasRole('instructor')) {
        $data['totalQuestions'] = \App\Models\Question::count();
        $data['totalExams'] = \App\Models\Exam::count();
    }

    return Inertia::render('Dashboard', $data);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/verify-identity', [OnboardingController::class, 'verifyIdentity'])->name('verify-identity');
    Route::post('/upload-identity', [OnboardingController::class, 'uploadIdentity'])->name('upload-identity');
});

Route::middleware(['auth', 'role:admin,superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/verify-users', [VerificationController::class, 'index'])->name('verify-users');
    Route::post('/verify-users/{user}/approve', [VerificationController::class, 'approve'])->name('verify-users.approve');
    Route::post('/verify-users/{user}/reject', [VerificationController::class, 'reject'])->name('verify-users.reject');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Load module routes
foreach (glob(app_path('Modules/*/routes.php')) as $moduleRoute) {
    require $moduleRoute;
}

require __DIR__.'/auth.php';
