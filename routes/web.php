<?php

use App\Http\Controllers\Admin\AnalitikController;
use App\Http\Controllers\Admin\CatatanAktivitasController;
use App\Http\Controllers\Admin\DataIndukController;
use App\Http\Controllers\Admin\InterpretasiSkorController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\PenggunaController;
use App\Http\Controllers\Admin\PratinjauKomponenController;
use App\Http\Controllers\Admin\SertifikatController;
use App\Http\Controllers\Admin\VerifikasiPenggunaController;
use App\Http\Controllers\DasborController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\OrientasiController;
use App\Http\Controllers\PratinjauController;
use App\Http\Controllers\ProfilController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/media/{path}', [MediaController::class, 'serve'])
    ->where('path', '.*')
    ->name('media.serve');

Route::get('/', function () {
    return Inertia::render('Welcome', [
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

Route::middleware(['auth', 'verified', 'verified.user'])->group(function () {
    Route::get('/dasbor', [DasborController::class, 'index'])->name('dasbor');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::middleware('permission:user.view')->group(function () {
            Route::get('/verifikasi-pengguna', [VerifikasiPenggunaController::class, 'index'])->name('verifikasi-pengguna');
            Route::get('/pengguna', [PenggunaController::class, 'index'])->name('pengguna.daftar');
        });

        Route::middleware('permission:user.edit')->group(function () {
            Route::post('/verifikasi-pengguna/{pengguna}/setujui', [VerifikasiPenggunaController::class, 'setujui'])->name('verifikasi-pengguna.setujui');
            Route::post('/verifikasi-pengguna/{pengguna}/tolak', [VerifikasiPenggunaController::class, 'tolak'])->name('verifikasi-pengguna.tolak');
            Route::post('/verifikasi-pengguna/{pengguna}/batalkan-verifikasi', [VerifikasiPenggunaController::class, 'batalkanVerifikasi'])->name('verifikasi-pengguna.batalkan-verifikasi');
            Route::patch('/pengguna/{pengguna}', [PenggunaController::class, 'update'])->name('pengguna.perbarui');
            Route::patch('/pengguna/{pengguna}/ubah-keaktifan', [PenggunaController::class, 'ubahKeaktifan'])->name('pengguna.ubah-keaktifan');
            Route::patch('/pengguna/{pengguna}/atur-ulang-sandi', [PenggunaController::class, 'aturUlangSandi'])->name('pengguna.atur-ulang-sandi');
        });

        Route::middleware('permission:master_data.view')->group(function () {
            Route::get('/data-induk/jenis-tes', [DataIndukController::class, 'daftarJenisTes'])->name('data-induk.jenis-tes.daftar');
            Route::get('/data-induk/keterampilan', [DataIndukController::class, 'daftarKeterampilan'])->name('data-induk.keterampilan.daftar');
            Route::get('/data-induk/keterampilan/jenis-tes/{jenisTes}', [DataIndukController::class, 'daftarKeterampilanPerJenisTes'])->name('data-induk.keterampilan.jenis-tes.daftar');
            Route::get('/data-induk/bagian-soal', [DataIndukController::class, 'daftarBagianSoal'])->name('data-induk.bagian-soal.daftar');
            Route::get('/data-induk/fakultas', [DataIndukController::class, 'daftarFakultas'])->name('data-induk.fakultas.daftar');
            Route::get('/data-induk/program-studi', [DataIndukController::class, 'daftarProgramStudi'])->name('data-induk.program-studi.daftar');
            Route::get('/data-induk/program-studi/fakultas/{fakultas}', [DataIndukController::class, 'daftarProgramStudiPerFakultas'])->name('data-induk.program-studi.fakultas.daftar');
            Route::get('/data-induk/program-studi/fakultas/{fakultas}/tambah-massal', [DataIndukController::class, 'tambahProgramStudiMassal'])->name('data-induk.program-studi.fakultas.tambah-massal');
            Route::get('/data-induk/interpretasi-skor', [InterpretasiSkorController::class, 'index'])->name('data-induk.interpretasi-skor.daftar');
            Route::get('/data-induk/interpretasi-skor/jenis-tes/{jenisTes}', [InterpretasiSkorController::class, 'detailPerJenisTes'])->name('data-induk.interpretasi-skor.jenis-tes.daftar');
            Route::get('/data-induk/interpretasi-skor/jenis-tes/{jenisTes}/tambah-massal', [InterpretasiSkorController::class, 'tambahMassal'])->name('data-induk.interpretasi-skor.jenis-tes.tambah-massal');
        });

        Route::middleware('permission:master_data.manage')->group(function () {
            Route::post('/data-induk/jenis-tes', [DataIndukController::class, 'simpanJenisTes'])->name('data-induk.jenis-tes.simpan');
            Route::put('/data-induk/jenis-tes/{jenisTes}', [DataIndukController::class, 'perbaruiJenisTes'])->name('data-induk.jenis-tes.perbarui');
            Route::delete('/data-induk/jenis-tes/{jenisTes}', [DataIndukController::class, 'hapusJenisTes'])->name('data-induk.jenis-tes.hapus');
            Route::post('/data-induk/keterampilan', [DataIndukController::class, 'simpanKeterampilan'])->name('data-induk.keterampilan.simpan');
            Route::post('/data-induk/keterampilan/urutkan', [DataIndukController::class, 'susunUlangKeterampilan'])->name('data-induk.keterampilan.urutkan');
            Route::put('/data-induk/keterampilan/{keterampilan}', [DataIndukController::class, 'perbaruiKeterampilan'])->name('data-induk.keterampilan.perbarui');
            Route::delete('/data-induk/keterampilan/{keterampilan}', [DataIndukController::class, 'hapusKeterampilan'])->name('data-induk.keterampilan.hapus');
            Route::post('/data-induk/bagian-soal', [DataIndukController::class, 'simpanBagianSoal'])->name('data-induk.bagian-soal.simpan');
            Route::post('/data-induk/bagian-soal/urutkan', [DataIndukController::class, 'susunUlangBagianSoal'])->name('data-induk.bagian-soal.urutkan');
            Route::put('/data-induk/bagian-soal/{bagianSoal}', [DataIndukController::class, 'perbaruiBagianSoal'])->name('data-induk.bagian-soal.perbarui');
            Route::delete('/data-induk/bagian-soal/{bagianSoal}', [DataIndukController::class, 'hapusBagianSoal'])->name('data-induk.bagian-soal.hapus');
            Route::post('/data-induk/fakultas', [DataIndukController::class, 'simpanFakultas'])->name('data-induk.fakultas.simpan');
            Route::put('/data-induk/fakultas/{fakultas}', [DataIndukController::class, 'perbaruiFakultas'])->name('data-induk.fakultas.perbarui');
            Route::delete('/data-induk/fakultas/{fakultas}', [DataIndukController::class, 'hapusFakultas'])->name('data-induk.fakultas.hapus');
            Route::post('/data-induk/program-studi', [DataIndukController::class, 'simpanProgramStudi'])->name('data-induk.program-studi.simpan');
            Route::post('/data-induk/program-studi/fakultas/{fakultas}/tambah-massal', [DataIndukController::class, 'simpanProgramStudiMassal'])->name('data-induk.program-studi.fakultas.simpan-massal');
            Route::put('/data-induk/program-studi/{programStudi}', [DataIndukController::class, 'perbaruiProgramStudi'])->name('data-induk.program-studi.perbarui');
            Route::delete('/data-induk/program-studi/{programStudi}', [DataIndukController::class, 'hapusProgramStudi'])->name('data-induk.program-studi.hapus');
            Route::post('/data-induk/interpretasi-skor', [InterpretasiSkorController::class, 'store'])->name('data-induk.interpretasi-skor.simpan');
            Route::post('/data-induk/interpretasi-skor/jenis-tes/{jenisTes}/tambah-massal', [InterpretasiSkorController::class, 'simpanMassal'])->name('data-induk.interpretasi-skor.jenis-tes.simpan-massal');
            Route::put('/data-induk/interpretasi-skor/{interpretasiSkor}', [InterpretasiSkorController::class, 'update'])->name('data-induk.interpretasi-skor.perbarui');
            Route::delete('/data-induk/interpretasi-skor/{interpretasiSkor}', [InterpretasiSkorController::class, 'destroy'])->name('data-induk.interpretasi-skor.hapus');
        });

        Route::middleware('permission:certificate.view')->get('/sertifikat', [SertifikatController::class, 'index'])->name('sertifikat.daftar');
        Route::middleware('permission:certificate.create')->post('/sertifikat', [SertifikatController::class, 'store'])->name('sertifikat.simpan');
        Route::middleware('permission:certificate.delete')->delete('/sertifikat/{sertifikat}', [SertifikatController::class, 'destroy'])->name('sertifikat.hapus');

        Route::middleware('permission:audit_log.view')->get('/catatan-aktivitas', [CatatanAktivitasController::class, 'index'])->name('catatan-aktivitas.daftar');

        Route::middleware('permission:settings.manage')->group(function () {
            Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.daftar');
            Route::patch('/pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.perbarui');
        });

        Route::middleware('permission:analytics.view')->get('/analitik', [AnalitikController::class, 'index'])->name('analitik.daftar');
        Route::middleware('permission:components.view')->get('/komponen', [PratinjauKomponenController::class, 'index'])->name('komponen.daftar');
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
