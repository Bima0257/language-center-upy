<?php

use App\Modules\Penjadwalan\Controllers\PenjadwalanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:schedule.view'])
    ->prefix('admin/periode-ujian')
    ->name('admin.periode-ujian.')
    ->group(function () {
        Route::get('/', [PenjadwalanController::class, 'daftar'])->name('daftar');
        Route::get('/{periodeUjian}', [PenjadwalanController::class, 'detail'])->name('detail');
    });

Route::middleware(['auth', 'permission:schedule.create'])
    ->prefix('admin/periode-ujian')
    ->name('admin.periode-ujian.')
    ->group(function () {
        Route::post('/{periodeUjian}/slot', [PenjadwalanController::class, 'simpanSlot'])->name('slot.simpan');
    });

Route::middleware(['auth', 'permission:schedule.edit'])
    ->prefix('admin/periode-ujian')
    ->name('admin.periode-ujian.')
    ->group(function () {
        Route::get('/{periodeUjian}/ubah', [PenjadwalanController::class, 'ubah'])->name('ubah');
        Route::put('/{periodeUjian}', [PenjadwalanController::class, 'perbarui'])->name('perbarui');
        Route::put('/slot/{slotUjian}', [PenjadwalanController::class, 'perbaruiSlot'])->name('slot.perbarui');
    });

Route::middleware(['auth', 'permission:schedule.delete'])
    ->prefix('admin/periode-ujian')
    ->name('admin.periode-ujian.')
    ->group(function () {
        Route::delete('/{periodeUjian}', [PenjadwalanController::class, 'hapus'])->name('hapus');
        Route::delete('/slot/{slotUjian}', [PenjadwalanController::class, 'hapusSlot'])->name('slot.hapus');
    });

Route::middleware(['auth', 'permission:schedule.create'])
    ->prefix('admin/ujian/{ujian}/periode-ujian')
    ->name('admin.periode-ujian.')
    ->group(function () {
        Route::get('/tambah', [PenjadwalanController::class, 'tambah'])->name('tambah');
        Route::post('/', [PenjadwalanController::class, 'simpan'])->name('simpan');
    });
