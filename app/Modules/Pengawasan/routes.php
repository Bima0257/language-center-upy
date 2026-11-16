<?php

use App\Modules\Pengawasan\Controllers\PengawasanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'route.permission'])
    ->prefix('pengawas')
    ->name('pengawas.')
    ->group(function () {
        Route::get('/dasbor', [PengawasanController::class, 'dasbor'])->name('dasbor');
        Route::get('/sesi/{sesiUjian}', [PengawasanController::class, 'tampilkanSesi'])->name('sesi.detail');
        Route::get('/sesi/{sesiUjian}/pelanggaran', [PengawasanController::class, 'pelanggaran'])->name('sesi.pelanggaran');
        Route::get('/ditandai', [PengawasanController::class, 'sesiDitandai'])->name('ditandai');
    });

Route::middleware(['auth', 'route.permission'])
    ->prefix('pengawas')
    ->name('pengawas.')
    ->group(function () {
        Route::post('/sesi/{sesiUjian}/hentikan', [PengawasanController::class, 'hentikan'])->name('sesi.hentikan');
    });

Route::middleware(['auth', 'route.permission'])
    ->prefix('pengawas')
    ->name('pengawas.')
    ->group(function () {
        Route::post('/sesi/{sesiUjian}/tinjauan', [PengawasanController::class, 'tinjau'])->name('sesi.tinjauan');
    });
