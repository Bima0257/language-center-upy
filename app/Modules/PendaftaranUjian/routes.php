<?php

use App\Modules\PendaftaranUjian\Controllers\PendaftaranUjianController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:exam_registration.apply'])
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

Route::middleware(['auth', 'permission:registration_period.view'])
    ->prefix('admin/pendaftaran-ujian')
    ->name('admin.pendaftaran-ujian.')
    ->group(function () {
        Route::get('/', [PendaftaranUjianController::class, 'daftar'])->name('daftar');
        Route::get('/{periodePendaftaranUjian}/peserta', [PendaftaranUjianController::class, 'peserta'])
            ->whereUuid('periodePendaftaranUjian')
            ->name('peserta');
        Route::get('/{periodePendaftaranUjian}', [PendaftaranUjianController::class, 'detail'])
            ->whereUuid('periodePendaftaranUjian')
            ->name('detail');
    });

Route::middleware(['auth', 'permission:registration_period.create'])
    ->prefix('admin/pendaftaran-ujian')
    ->name('admin.pendaftaran-ujian.')
    ->group(function () {
        Route::get('/tambah', [PendaftaranUjianController::class, 'tambah'])->name('tambah');
        Route::post('/', [PendaftaranUjianController::class, 'simpan'])->name('simpan');
    });

Route::middleware(['auth', 'permission:registration_period.edit'])
    ->prefix('admin/pendaftaran-ujian')
    ->name('admin.pendaftaran-ujian.')
    ->group(function () {
        Route::get('/{periodePendaftaranUjian}/ubah', [PendaftaranUjianController::class, 'ubah'])
            ->whereUuid('periodePendaftaranUjian')
            ->name('ubah');
        Route::put('/{periodePendaftaranUjian}', [PendaftaranUjianController::class, 'perbarui'])
            ->whereUuid('periodePendaftaranUjian')
            ->name('perbarui');
    });

Route::middleware(['auth', 'permission:registration_period.delete'])
    ->prefix('admin/pendaftaran-ujian')
    ->name('admin.pendaftaran-ujian.')
    ->group(function () {
        Route::delete('/{periodePendaftaranUjian}', [PendaftaranUjianController::class, 'hapus'])
            ->whereUuid('periodePendaftaranUjian')
            ->name('hapus');
    });

Route::middleware(['auth', 'permission:exam_registration.manage'])
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
