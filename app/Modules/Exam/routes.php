<?php

use App\Modules\Exam\Controllers\ContentLibraryController;
use App\Modules\Exam\Controllers\ExamController;
use App\Modules\Exam\Controllers\ExamSectionController;
use App\Modules\Exam\Controllers\QuestionGroupController;
use App\Modules\Exam\Controllers\QuestionController;
use App\Modules\Exam\Controllers\TagController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:instructor,admin,superadmin'])
    ->prefix('content-library')
    ->name('content-library.')
    ->group(function () {
        Route::get('/', [ContentLibraryController::class, 'index'])->name('index');
        Route::get('/create', [ContentLibraryController::class, 'create'])->name('create');
        Route::post('/', [ContentLibraryController::class, 'store'])->name('store');
        Route::get('/{question}/edit', [ContentLibraryController::class, 'edit'])->name('edit');
        Route::put('/questions/{question}', [ContentLibraryController::class, 'update'])->name('update');
        Route::delete('/questions/{question}', [ContentLibraryController::class, 'destroy'])->name('destroy');
        Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
        Route::post('/tags', [TagController::class, 'store'])->name('tags.store');
        Route::put('/tags/{tag}', [TagController::class, 'update'])->name('tags.update');
        Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');
    });

Route::middleware(['auth', 'role:instructor,admin,superadmin'])
    ->prefix('admin/exams')
    ->name('admin.exams.')
    ->group(function () {
        Route::get('/', [ExamController::class, 'index'])->name('index');
        Route::get('/create', [ExamController::class, 'create'])->name('create');
        Route::post('/', [ExamController::class, 'store'])->name('store');
        Route::get('/{exam}', [ExamController::class, 'show'])->name('show');
        Route::get('/{exam}/edit', [ExamController::class, 'edit'])->name('edit');
        Route::put('/{exam}', [ExamController::class, 'update'])->name('update');
        Route::delete('/{exam}', [ExamController::class, 'destroy'])->name('destroy');

        Route::post('/{exam}/sections', [ExamSectionController::class, 'store'])->name('sections.store');
        Route::put('/{exam}/sections/{section}', [ExamSectionController::class, 'update'])->name('sections.update');
        Route::delete('/{exam}/sections/{section}', [ExamSectionController::class, 'destroy'])->name('sections.destroy');

        Route::post('/sections/{section}/groups', [QuestionGroupController::class, 'store'])->name('groups.store');
        Route::put('/groups/{group}', [QuestionGroupController::class, 'update'])->name('groups.update');
        Route::delete('/groups/{group}', [QuestionGroupController::class, 'destroy'])->name('groups.destroy');

        Route::post('/groups/{group}/questions', [QuestionController::class, 'store'])->name('questions.store');
        Route::post('/groups/{group}/questions/bulk', [QuestionController::class, 'bulkStore'])->name('questions.bulk');
        Route::post('/groups/{group}/questions/import', [QuestionController::class, 'importFile'])->name('questions.import');
        Route::put('/questions/{question}', [QuestionController::class, 'update'])->name('questions.update');
        Route::delete('/questions/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');
    });
