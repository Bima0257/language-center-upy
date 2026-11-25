<?php

use App\Modules\PendaftaranUjian\Controllers\PendaftaranUjianController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'route.permission'])
    ->prefix('ujian')
    ->name('ujian.')
    ->group(function () {
        Route::get('/pendaftaran', [PendaftaranUjianController::class, 'pendaftaran'])
            ->name('pendaftaran.daftar');
        Route::get('/resmi', [PendaftaranUjianController::class, 'resmi'])
            ->name('resmi');
    });

Route::middleware(['auth', 'route.permission'])
    ->prefix('ujian')
    ->name('ujian.')
    ->group(function () {
        Route::post('/slot/{slotUjian}/pendaftaran', [PendaftaranUjianController::class, 'ajukan'])
            ->whereUuid('slotUjian')
            ->name('pendaftaran.ajukan');
        Route::delete('/pendaftaran/{pendaftaranUjian}', [PendaftaranUjianController::class, 'batalkan'])
            ->whereUuid('pendaftaranUjian')
            ->name('pendaftaran.batalkan');
    });

Route::middleware(['auth', 'route.permission'])
    ->prefix('admin/pendaftaran-ujian')
    ->name('admin.pendaftaran-ujian.')
    ->group(function () {
        Route::get('/', [PendaftaranUjianController::class, 'daftar'])->name('daftar');
        Route::get('/peserta', [PendaftaranUjianController::class, 'pesertaSemua'])->name('peserta-semua');
    });

Route::middleware(['auth', 'route.permission'])
    ->prefix('admin/pendaftaran-ujian')
    ->name('admin.pendaftaran-ujian.')
    ->group(function () {
        Route::post('/', [PendaftaranUjianController::class, 'simpan'])->name('simpan');
    });

Route::middleware(['auth', 'route.permission'])
    ->prefix('admin/pendaftaran-ujian')
    ->name('admin.pendaftaran-ujian.')
    ->group(function () {
        Route::put('/{periodePendaftaranUjian}', [PendaftaranUjianController::class, 'perbarui'])
            ->whereUuid('periodePendaftaranUjian')
            ->name('perbarui');
    });

Route::middleware(['auth', 'route.permission'])
    ->prefix('admin/pendaftaran-ujian')
    ->name('admin.pendaftaran-ujian.')
    ->group(function () {
        Route::delete('/{periodePendaftaranUjian}', [PendaftaranUjianController::class, 'hapus'])
            ->whereUuid('periodePendaftaranUjian')
            ->name('hapus');
    });

Route::middleware(['auth', 'route.permission'])
    ->prefix('admin/pendaftaran-ujian')
    ->name('admin.pendaftaran-ujian.')
    ->group(function () {
        Route::patch('/{pendaftaranUjian}/setujui', [PendaftaranUjianController::class, 'setujui'])
            ->whereUuid('pendaftaranUjian')
            ->name('setujui');
        Route::patch('/{pendaftaranUjian}/tolak', [PendaftaranUjianController::class, 'tolak'])
            ->whereUuid('pendaftaranUjian')
            ->name('tolak');
        Route::patch('/{pendaftaranUjian}/batalkan', [PendaftaranUjianController::class, 'batalkanOlehAdmin'])
            ->whereUuid('pendaftaranUjian')
            ->name('batalkan');
    });
