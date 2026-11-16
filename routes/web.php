<?php

use App\Http\Controllers\Admin\AnalitikController;
use App\Http\Controllers\Admin\CatatanAktivitasController;
use App\Http\Controllers\Admin\DataIndukController;
use App\Http\Controllers\Admin\InterpretasiSkorController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\PratinjauKomponenController;
use App\Http\Controllers\Admin\SertifikatController;
use App\Http\Controllers\DasborController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\OrientasiController;
use App\Http\Controllers\PratinjauController;
use App\Http\Controllers\ProfilController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->get('/media/{path}', [MediaController::class, 'serve'])
    ->where('path', '.*')
    ->name('media.serve');

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dasbor');
    }

    return Inertia::render('Welcome/index', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware('auth')->get('/preview/exam-take', [PratinjauController::class, 'pratinjauPengerjaan'])
    ->name('preview.exam-take');

Route::middleware('auth')->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/verify-identity', [OrientasiController::class, 'verifikasiIdentitas'])->name('verify-identity');
    Route::post('/upload-identity', [OrientasiController::class, 'unggahIdentitas'])->name('upload-identity');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dasbor', [DasborController::class, 'index'])->name('dasbor');

    Route::middleware('route.permission')->prefix('admin')->name('admin.')->group(function () {
        Route::middleware('route.permission')->group(function () {
            Route::get('/data-induk/program-tes', [DataIndukController::class, 'daftarProgramTes'])->name('data-induk.program-tes.daftar');
            Route::get('/data-induk/section', [DataIndukController::class, 'daftarSection'])->name('data-induk.section.daftar');
            Route::get('/data-induk/section/program-tes/{programTes}', [DataIndukController::class, 'daftarSectionPerProgramTes'])->name('data-induk.section.program-tes.daftar');
            Route::get('/data-induk/part-soal', [DataIndukController::class, 'daftarPartSoal'])->name('data-induk.part-soal.daftar');
            Route::get('/data-induk/fakultas', [DataIndukController::class, 'daftarFakultas'])->name('data-induk.fakultas.daftar');
            Route::get('/data-induk/program-studi', [DataIndukController::class, 'daftarProgramStudi'])->name('data-induk.program-studi.daftar');
            Route::get('/data-induk/program-studi/fakultas/{fakultas}', [DataIndukController::class, 'daftarProgramStudiPerFakultas'])->name('data-induk.program-studi.fakultas.daftar');
            Route::get('/data-induk/program-studi/fakultas/{fakultas}/tambah-massal', [DataIndukController::class, 'tambahProgramStudiMassal'])->name('data-induk.program-studi.fakultas.tambah-massal');
            Route::get('/data-induk/interpretasi-skor', [InterpretasiSkorController::class, 'index'])->name('data-induk.interpretasi-skor.daftar');
            Route::get('/data-induk/interpretasi-skor/program-tes/{programTes}', [InterpretasiSkorController::class, 'detailPerProgramTes'])->name('data-induk.interpretasi-skor.program-tes.daftar');
            Route::get('/data-induk/interpretasi-skor/program-tes/{programTes}/tambah-massal', [InterpretasiSkorController::class, 'tambahMassal'])->name('data-induk.interpretasi-skor.program-tes.tambah-massal');
        });

        Route::middleware('route.permission')->group(function () {
            Route::post('/data-induk/program-tes', [DataIndukController::class, 'simpanProgramTes'])->name('data-induk.program-tes.simpan');
            Route::put('/data-induk/program-tes/{programTes}', [DataIndukController::class, 'perbaruiProgramTes'])->name('data-induk.program-tes.perbarui');
            Route::delete('/data-induk/program-tes/{programTes}', [DataIndukController::class, 'hapusProgramTes'])->name('data-induk.program-tes.hapus');
            Route::post('/data-induk/section', [DataIndukController::class, 'simpanSection'])->name('data-induk.section.simpan');
            Route::post('/data-induk/section/urutkan', [DataIndukController::class, 'susunUlangSection'])->name('data-induk.section.urutkan');
            Route::put('/data-induk/section/{section}', [DataIndukController::class, 'perbaruiSection'])->name('data-induk.section.perbarui');
            Route::delete('/data-induk/section/{section}', [DataIndukController::class, 'hapusSection'])->name('data-induk.section.hapus');
            Route::post('/data-induk/part-soal', [DataIndukController::class, 'simpanPartSoal'])->name('data-induk.part-soal.simpan');
            Route::post('/data-induk/part-soal/urutkan', [DataIndukController::class, 'susunUlangPartSoal'])->name('data-induk.part-soal.urutkan');
            Route::put('/data-induk/part-soal/{partSoal}', [DataIndukController::class, 'perbaruiPartSoal'])->name('data-induk.part-soal.perbarui');
            Route::delete('/data-induk/part-soal/{partSoal}', [DataIndukController::class, 'hapusPartSoal'])->name('data-induk.part-soal.hapus');
            Route::post('/data-induk/fakultas', [DataIndukController::class, 'simpanFakultas'])->name('data-induk.fakultas.simpan');
            Route::put('/data-induk/fakultas/{fakultas}', [DataIndukController::class, 'perbaruiFakultas'])->name('data-induk.fakultas.perbarui');
            Route::delete('/data-induk/fakultas/{fakultas}', [DataIndukController::class, 'hapusFakultas'])->name('data-induk.fakultas.hapus');
            Route::post('/data-induk/program-studi', [DataIndukController::class, 'simpanProgramStudi'])->name('data-induk.program-studi.simpan');
            Route::post('/data-induk/program-studi/fakultas/{fakultas}/tambah-massal', [DataIndukController::class, 'simpanProgramStudiMassal'])->name('data-induk.program-studi.fakultas.simpan-massal');
            Route::put('/data-induk/program-studi/{programStudi}', [DataIndukController::class, 'perbaruiProgramStudi'])->name('data-induk.program-studi.perbarui');
            Route::delete('/data-induk/program-studi/{programStudi}', [DataIndukController::class, 'hapusProgramStudi'])->name('data-induk.program-studi.hapus');
            Route::post('/data-induk/interpretasi-skor', [InterpretasiSkorController::class, 'store'])->name('data-induk.interpretasi-skor.simpan');
            Route::post('/data-induk/interpretasi-skor/program-tes/{programTes}/tambah-massal', [InterpretasiSkorController::class, 'simpanMassal'])->name('data-induk.interpretasi-skor.program-tes.simpan-massal');
            Route::put('/data-induk/interpretasi-skor/{interpretasiSkor}', [InterpretasiSkorController::class, 'update'])->name('data-induk.interpretasi-skor.perbarui');
            Route::delete('/data-induk/interpretasi-skor/{interpretasiSkor}', [InterpretasiSkorController::class, 'destroy'])->name('data-induk.interpretasi-skor.hapus');
        });

        Route::middleware('route.permission')->get('/sertifikat', [SertifikatController::class, 'index'])->name('sertifikat.daftar');
        Route::middleware('route.permission')->post('/sertifikat', [SertifikatController::class, 'store'])->name('sertifikat.simpan');
        Route::middleware('route.permission')->delete('/sertifikat/{sertifikat}', [SertifikatController::class, 'destroy'])->name('sertifikat.hapus');

        Route::middleware('route.permission')->get('/catatan-aktivitas', [CatatanAktivitasController::class, 'index'])->name('catatan-aktivitas.daftar');

        Route::middleware('route.permission')->group(function () {
            Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.daftar');
            Route::patch('/pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.perbarui');
        });

        Route::middleware('route.permission')->get('/analitik', [AnalitikController::class, 'index'])->name('analitik.daftar');
        Route::middleware('route.permission')->get('/komponen', [PratinjauKomponenController::class, 'index'])->name('komponen.daftar');
    });

    Route::get('/profil', [ProfilController::class, 'edit'])->name('profile.edit');
    Route::patch('/profil', [ProfilController::class, 'update'])->name('profile.update');
    Route::delete('/profil', [ProfilController::class, 'destroy'])->name('profile.destroy');

    // Load module routes
    foreach (glob(app_path('Modules/*/routes.php')) as $moduleRoute) {
        require $moduleRoute;
    }
});

require __DIR__.'/auth.php';
