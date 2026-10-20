<?php

use App\Modules\PelaksanaanUjian\Controllers\SesiUjianController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'permission:exam_session.take'])
    ->prefix('ujian')
    ->name('ujian.')
    ->group(function () {
        Route::get('/tersedia', [SesiUjianController::class, 'tersedia'])->name('tersedia');
        Route::get('/riwayat', [SesiUjianController::class, 'riwayat'])->name('riwayat');
        Route::get('/jadwal', [SesiUjianController::class, 'jadwal'])->name('jadwal');
        Route::get('/sertifikat', [SesiUjianController::class, 'sertifikat'])->name('sertifikat');
        Route::get('/slot/{slotUjian}/pemeriksaan-awal', [SesiUjianController::class, 'pemeriksaanAwal'])->name('pemeriksaan-awal');
        Route::post('/slot/{slotUjian}/mulai', [SesiUjianController::class, 'mulai'])->name('mulai');

        Route::middleware('exam.session.active')->group(function () {
            Route::get('/sesi/{sesiUjian}/{bagianUjian?}', [SesiUjianController::class, 'kerjakan'])->name('kerjakan');
            Route::post('/sesi/{sesiUjian}/jawaban', [SesiUjianController::class, 'simpanJawaban'])->name('simpan-jawaban');
            Route::post('/sesi/{sesiUjian}/selesaikan-bagian', [SesiUjianController::class, 'selesaikanBagian'])->name('selesaikan-bagian');
            Route::post('/sesi/{sesiUjian}/serahkan', [SesiUjianController::class, 'serahkan'])->name('serahkan');
            Route::post('/sesi/{sesiUjian}/keaktifan', [SesiUjianController::class, 'keaktifan'])->name('keaktifan');
            Route::post('/sesi/{sesiUjian}/catat-pelanggaran', [SesiUjianController::class, 'catatPelanggaran'])->name('catat-pelanggaran');
        });
    });
