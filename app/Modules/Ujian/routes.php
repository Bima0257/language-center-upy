<?php

use App\Modules\Ujian\Controllers\BankSoalController;
use App\Modules\Ujian\Controllers\DemoUjianController;
use App\Modules\Ujian\Controllers\MateriSoalController;
use App\Modules\Ujian\Controllers\PartUjianController;
use App\Modules\Ujian\Controllers\PustakaSoalController;
use App\Modules\Ujian\Controllers\SoalController;
use App\Modules\Ujian\Controllers\UjianController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'route.permission'])
    ->prefix('pustaka-soal')
    ->name('pustaka-soal.')
    ->group(function () {
        Route::get('/', [PustakaSoalController::class, 'daftar'])->name('daftar');
    });

Route::middleware(['auth', 'route.permission'])
    ->prefix('pustaka-soal')
    ->name('pustaka-soal.')
    ->group(function () {
        Route::get('/tambah', [PustakaSoalController::class, 'tambah'])->name('tambah');
        Route::post('/', [PustakaSoalController::class, 'simpan'])->name('simpan');
        Route::post('/materi-soal', [MateriSoalController::class, 'simpan'])->name('materi-soal.simpan');
    });

Route::middleware(['auth', 'route.permission'])
    ->prefix('pustaka-soal')
    ->name('pustaka-soal.')
    ->group(function () {
        Route::get('/soal/{soal}/ubah', [PustakaSoalController::class, 'ubah'])->whereUuid('soal')->name('ubah');
        Route::put('/soal/{soal}', [PustakaSoalController::class, 'perbarui'])->whereUuid('soal')->name('perbarui');
        Route::put('/materi-soal/{materiSoal}', [MateriSoalController::class, 'perbarui'])->name('materi-soal.perbarui');
        Route::delete('/materi-soal/{materiSoal}/media/{jenisMedia}', [MateriSoalController::class, 'hapusMedia'])
            ->whereIn('jenisMedia', ['audio', 'image'])
            ->name('materi-soal.media.hapus');
    });

Route::middleware(['auth', 'route.permission'])
    ->prefix('pustaka-soal')
    ->name('pustaka-soal.')
    ->group(function () {
        Route::delete('/soal/{soal}', [PustakaSoalController::class, 'hapus'])->whereUuid('soal')->name('hapus');
        Route::delete('/materi-soal/{materiSoal}', [MateriSoalController::class, 'hapus'])->name('materi-soal.hapus');
    });

Route::middleware(['auth', 'route.permission'])
    ->prefix('pustaka-soal/bank-soal')
    ->name('pustaka-soal.bank-soal.')
    ->group(function () {
        Route::get('/program-tes/{programTes}', [BankSoalController::class, 'daftarProgramTes'])
            ->whereUuid('programTes')
            ->name('program-tes');
        Route::get('/', [BankSoalController::class, 'daftar'])->name('daftar');
    });

Route::middleware(['auth', 'route.permission'])
    ->prefix('pustaka-soal/bank-soal')
    ->name('pustaka-soal.bank-soal.')
    ->group(function () {
        Route::post('/', [BankSoalController::class, 'simpan'])->name('simpan');
    });

Route::middleware(['auth', 'route.permission'])
    ->prefix('pustaka-soal/bank-soal')
    ->name('pustaka-soal.bank-soal.')
    ->group(function () {
        Route::put('/{bankSoal}', [BankSoalController::class, 'perbarui'])->name('perbarui');
    });

Route::middleware(['auth', 'route.permission'])
    ->prefix('pustaka-soal/bank-soal')
    ->name('pustaka-soal.bank-soal.')
    ->group(function () {
        Route::delete('/{bankSoal}', [BankSoalController::class, 'hapus'])->name('hapus');
    });

Route::middleware(['auth', 'route.permission'])
    ->prefix('admin/ujian')
    ->name('admin.ujian.')
    ->group(function () {
        Route::get('/tambah', [UjianController::class, 'tambah'])->name('tambah');
        Route::post('/', [UjianController::class, 'simpan'])->name('simpan');
    });

Route::middleware(['auth', 'route.permission'])
    ->prefix('admin/ujian')
    ->name('admin.ujian.')
    ->group(function () {
        Route::get('/', [UjianController::class, 'daftar'])->name('daftar');
        Route::get('/{ujian}/pratinjau', [UjianController::class, 'pratinjau'])->name('pratinjau');
        Route::get('/{ujian}', [UjianController::class, 'detail'])->name('detail');
    });

Route::middleware(['auth', 'route.permission'])
    ->prefix('admin/demo-ujian')
    ->name('admin.demo-ujian.')
    ->group(function () {
        Route::get('/', [DemoUjianController::class, 'daftar'])->name('daftar');
        Route::get('/{ujian}', [DemoUjianController::class, 'mulai'])->whereUuid('ujian')->name('mulai');
        Route::post('/{ujian}/skor', [DemoUjianController::class, 'skor'])->whereUuid('ujian')->name('skor');
    });

Route::middleware(['auth', 'route.permission'])
    ->prefix('admin/ujian')
    ->name('admin.ujian.')
    ->group(function () {
        Route::put('/{ujian}', [UjianController::class, 'perbarui'])->name('perbarui');
        Route::post('/{ujian}/sinkronkan-part', [UjianController::class, 'sinkronkanPart'])->name('sinkronkan-part');
        Route::put('/{ujian}/part/{partUjian}', [PartUjianController::class, 'perbarui'])->name('part.perbarui');
        Route::patch('/{ujian}/part/{partUjian}/soal/urutan', [PartUjianController::class, 'susunUlangSoal'])->name('part.soal.urutan');
        Route::patch('/{ujian}/part/{partUjian}/part-soal/urutan', [PartUjianController::class, 'susunUlangPartSoal'])->name('part.part-soal.urutan');
        Route::patch('/{ujian}/part/{partUjian}/susunan', [PartUjianController::class, 'simpanSusunan'])->name('part.susunan');
    });

Route::middleware(['auth', 'route.permission'])
    ->prefix('admin/ujian')
    ->name('admin.ujian.')
    ->group(function () {
        Route::post('/soal', [SoalController::class, 'simpan'])->name('soal.simpan');
    });

Route::middleware(['auth', 'route.permission'])
    ->prefix('admin/ujian')
    ->name('admin.ujian.')
    ->group(function () {
        Route::put('/soal/{soal}', [SoalController::class, 'perbarui'])->whereUuid('soal')->name('soal.perbarui');
    });

Route::middleware(['auth', 'route.permission'])
    ->prefix('admin/ujian')
    ->name('admin.ujian.')
    ->group(function () {
        Route::delete('/{ujian}', [UjianController::class, 'hapus'])->name('hapus');
        Route::delete('/{ujian}/part/{partUjian}', [PartUjianController::class, 'hapus'])->name('part.hapus');
    });

Route::middleware(['auth', 'route.permission'])
    ->prefix('admin/ujian')
    ->name('admin.ujian.')
    ->group(function () {
        Route::delete('/soal/{soal}', [SoalController::class, 'hapus'])->whereUuid('soal')->name('soal.hapus');
    });
