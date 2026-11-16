<?php

use App\Modules\Laporan\Controllers\LaporanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'route.permission'])
    ->prefix('admin/laporan')
    ->name('admin.laporan.')
    ->group(function () {
        Route::get('/sesi', [LaporanController::class, 'integritas'])->name('sesi');
    });

Route::middleware(['auth', 'route.permission'])
    ->prefix('admin/laporan')
    ->name('admin.laporan.')
    ->group(function () {
        Route::get('/sesi/ekspor/{sesiUjian}', [LaporanController::class, 'eksporIntegritas'])->name('sesi.ekspor');
    });
