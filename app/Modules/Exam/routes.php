<?php

use App\Modules\Exam\Controllers\ContentLibraryController;
use App\Modules\Exam\Controllers\ExamController;
use App\Modules\Exam\Controllers\ExamSectionController;
use App\Modules\Exam\Controllers\PassageController;
use App\Modules\Exam\Controllers\QuestionBankController;
use App\Modules\Exam\Controllers\QuestionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:instructor,admin,superadmin'])
    ->prefix('content-library')
    ->name('content-library.')
    ->group(function () {
        Route::get('/', [ContentLibraryController::class, 'index'])->name('index');
        Route::get('/create', [ContentLibraryController::class, 'create'])->name('create');
        Route::post('/', [ContentLibraryController::class, 'store'])->name('store');
        Route::get('/{question}/edit', [ContentLibraryController::class, 'edit'])->whereNumber('question')->name('edit');
        Route::put('/questions/{question}', [ContentLibraryController::class, 'update'])->whereNumber('question')->name('update');
        Route::delete('/questions/{question}', [ContentLibraryController::class, 'destroy'])->whereNumber('question')->name('destroy');

        Route::get('/passages', [PassageController::class, 'index'])->name('passages.index');
        Route::post('/passages', [PassageController::class, 'store'])->name('passages.store');
        Route::put('/passages/{passage}', [PassageController::class, 'update'])->name('passages.update');
        Route::delete('/passages/{passage}', [PassageController::class, 'destroy'])->name('passages.destroy');
    });

Route::middleware(['auth', 'role:admin,superadmin'])
    ->prefix('content-library')
    ->name('content-library.')
    ->group(function () {
        Route::patch('/questions/{question}/review', [ContentLibraryController::class, 'review'])->name('review');
        Route::patch('/questions/bulk-review', [ContentLibraryController::class, 'bulkReview'])->name('bulk-review');

        Route::get('/question-banks', [QuestionBankController::class, 'index'])->name('question-banks.index');
        Route::post('/question-banks', [QuestionBankController::class, 'store'])->name('question-banks.store');
        Route::put('/question-banks/{questionBank}', [QuestionBankController::class, 'update'])->name('question-banks.update');
        Route::delete('/question-banks/{questionBank}', [QuestionBankController::class, 'destroy'])->name('question-banks.destroy');
    });

Route::middleware(['auth', 'role:admin,superadmin'])
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

        Route::post('/questions', [QuestionController::class, 'store'])->name('questions.store');
        Route::post('/questions/bulk', [QuestionController::class, 'bulkStore'])->name('questions.bulk');
        Route::post('/questions/import', [QuestionController::class, 'importFile'])->name('questions.import');
        Route::put('/questions/{question}', [QuestionController::class, 'update'])->name('questions.update');
        Route::delete('/questions/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');
    });
