<?php

use App\Modules\Ujian\Controllers\BagianUjianController;
use App\Modules\Ujian\Controllers\BankSoalController;
use App\Modules\Ujian\Controllers\MateriSoalController;
use App\Modules\Ujian\Controllers\PustakaSoalController;
use App\Modules\Ujian\Controllers\SoalController;
use App\Modules\Ujian\Controllers\UjianController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:question.view'])
    ->prefix('pustaka-soal')
    ->name('pustaka-soal.')
    ->group(function () {
        Route::get('/', [PustakaSoalController::class, 'daftar'])->name('daftar');
        Route::get('/soal/{soal}/pratinjau', [PustakaSoalController::class, 'pratinjau'])->whereUuid('soal')->name('pratinjau');
    });

Route::middleware(['auth', 'permission:question.create'])
    ->prefix('pustaka-soal')
    ->name('pustaka-soal.')
    ->group(function () {
        Route::get('/tambah', [PustakaSoalController::class, 'tambah'])->name('tambah');
        Route::post('/', [PustakaSoalController::class, 'simpan'])->name('simpan');
        Route::post('/materi-soal', [MateriSoalController::class, 'simpan'])->name('materi-soal.simpan');
    });

Route::middleware(['auth', 'permission:question.edit'])
    ->prefix('pustaka-soal')
    ->name('pustaka-soal.')
    ->group(function () {
        Route::get('/soal/{soal}/ubah', [PustakaSoalController::class, 'ubah'])->whereUuid('soal')->name('ubah');
        Route::put('/soal/{soal}', [PustakaSoalController::class, 'perbarui'])->whereUuid('soal')->name('perbarui');
        Route::put('/materi-soal/{materiSoal}', [MateriSoalController::class, 'perbarui'])->name('materi-soal.perbarui');
    });

Route::middleware(['auth', 'permission:question.delete'])
    ->prefix('pustaka-soal')
    ->name('pustaka-soal.')
    ->group(function () {
        Route::delete('/soal/{soal}', [PustakaSoalController::class, 'hapus'])->whereUuid('soal')->name('hapus');
        Route::delete('/materi-soal/{materiSoal}', [MateriSoalController::class, 'hapus'])->name('materi-soal.hapus');
    });

Route::middleware(['auth', 'permission:question.view'])
    ->prefix('pustaka-soal/bank-soal')
    ->name('pustaka-soal.bank-soal.')
    ->group(function () {
        Route::get('/kategori/{jenisTes}', [BankSoalController::class, 'daftarKategori'])
            ->whereUuid('jenisTes')
            ->name('kategori');
        Route::get('/', [BankSoalController::class, 'daftar'])->name('daftar');
    });

Route::middleware(['auth', 'permission:question.create'])
    ->prefix('pustaka-soal/bank-soal')
    ->name('pustaka-soal.bank-soal.')
    ->group(function () {
        Route::post('/', [BankSoalController::class, 'simpan'])->name('simpan');
    });

Route::middleware(['auth', 'permission:question.edit'])
    ->prefix('pustaka-soal/bank-soal')
    ->name('pustaka-soal.bank-soal.')
    ->group(function () {
        Route::put('/{bankSoal}', [BankSoalController::class, 'perbarui'])->name('perbarui');
    });

Route::middleware(['auth', 'permission:question.delete'])
    ->prefix('pustaka-soal/bank-soal')
    ->name('pustaka-soal.bank-soal.')
    ->group(function () {
        Route::delete('/{bankSoal}', [BankSoalController::class, 'hapus'])->name('hapus');
    });

Route::middleware(['auth', 'permission:exam.create'])
    ->prefix('admin/ujian')
    ->name('admin.ujian.')
    ->group(function () {
        Route::get('/tambah', [UjianController::class, 'tambah'])->name('tambah');
        Route::post('/', [UjianController::class, 'simpan'])->name('simpan');
    });

Route::middleware(['auth', 'permission:exam.manage'])
    ->prefix('admin/ujian')
    ->name('admin.ujian.')
    ->group(function () {
        Route::get('/', [UjianController::class, 'daftar'])->name('daftar');
        Route::get('/{ujian}', [UjianController::class, 'detail'])->name('detail');
    });

Route::middleware(['auth', 'permission:exam.edit'])
    ->prefix('admin/ujian')
    ->name('admin.ujian.')
    ->group(function () {
        Route::get('/{ujian}/ubah', [UjianController::class, 'ubah'])->name('ubah');
        Route::put('/{ujian}', [UjianController::class, 'perbarui'])->name('perbarui');
        Route::post('/{ujian}/sinkronkan-bagian', [UjianController::class, 'sinkronkanBagian'])->name('sinkronkan-bagian');
        Route::put('/{ujian}/bagian/{bagianUjian}', [BagianUjianController::class, 'perbarui'])->name('bagian.perbarui');
        Route::post('/{ujian}/bagian/{bagianUjian}/soal', [BagianUjianController::class, 'lampirkanSoal'])->name('bagian.soal.simpan');
        Route::patch('/{ujian}/bagian/{bagianUjian}/soal/urutan', [BagianUjianController::class, 'susunUlangSoal'])->name('bagian.soal.urutan');
        Route::patch('/{ujian}/bagian/{bagianUjian}/bagian-soal/urutan', [BagianUjianController::class, 'susunUlangBagianSoal'])->name('bagian.bagian-soal.urutan');
        Route::patch('/{ujian}/bagian/{bagianUjian}/susunan', [BagianUjianController::class, 'simpanSusunan'])->name('bagian.susunan');
    });

Route::middleware(['auth', 'permission:question.create'])
    ->prefix('admin/ujian')
    ->name('admin.ujian.')
    ->group(function () {
        Route::post('/soal', [SoalController::class, 'simpan'])->name('soal.simpan');
        Route::post('/soal/massal', [SoalController::class, 'simpanMassal'])->name('soal.massal');
        Route::post('/soal/impor', [SoalController::class, 'imporBerkas'])->name('soal.impor');
    });

Route::middleware(['auth', 'permission:question.edit'])
    ->prefix('admin/ujian')
    ->name('admin.ujian.')
    ->group(function () {
        Route::put('/soal/{soal}', [SoalController::class, 'perbarui'])->name('soal.perbarui');
    });

Route::middleware(['auth', 'permission:exam.delete'])
    ->prefix('admin/ujian')
    ->name('admin.ujian.')
    ->group(function () {
        Route::delete('/{ujian}', [UjianController::class, 'hapus'])->name('hapus');
        Route::delete('/{ujian}/bagian/{bagianUjian}', [BagianUjianController::class, 'hapus'])->name('bagian.hapus');
    });

Route::middleware(['auth', 'permission:question.delete'])
    ->prefix('admin/ujian')
    ->name('admin.ujian.')
    ->group(function () {
        Route::delete('/soal/{soal}', [SoalController::class, 'hapus'])->name('soal.hapus');
    });
