<?php

use App\Modules\Penilaian\Controllers\AturanPenilaianController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'route.permission'])
    ->prefix('admin/data-induk/aturan-penilaian')
    ->name('admin.data-induk.aturan-penilaian.')
    ->group(function () {
        Route::get('/', [AturanPenilaianController::class, 'daftar'])->name('daftar');
        Route::get('/program-tes/{programTes}', [AturanPenilaianController::class, 'detail'])->name('program-tes.daftar');
        Route::post('/program-tes/{programTes}/versi', [AturanPenilaianController::class, 'buatVersi'])->name('program-tes.versi.simpan');
        Route::post('/skema/{skema}/aktifkan', [AturanPenilaianController::class, 'aktifkan'])->name('skema.aktifkan');
        Route::post('/skema/{skema}/arsipkan', [AturanPenilaianController::class, 'arsipkan'])->name('skema.arsipkan');
        Route::post('/skema/{skema}/simulasi', [AturanPenilaianController::class, 'simulasi'])->name('skema.simulasi');
        Route::put('/skema/{skema}/section/{section}', [AturanPenilaianController::class, 'perbarui'])
            ->name('skema.section.perbarui');
    });
