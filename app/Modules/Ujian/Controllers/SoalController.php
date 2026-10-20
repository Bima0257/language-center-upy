<?php

namespace App\Modules\Ujian\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ujian\ImporBerkasRequest;
use App\Http\Requests\Ujian\ImporSoalMassalRequest;
use App\Http\Requests\Ujian\SimpanSoalRequest;
use App\Models\Soal;
use App\Modules\Ujian\Services\LayananSoal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class SoalController extends Controller
{
    public function __construct(
        private LayananSoal $layananSoal,
    ) {}

    public function simpan(SimpanSoalRequest $request): RedirectResponse
    {
        $this->layananSoal->simpan($request->validated());

        return back()->with('success', 'Soal berhasil ditambahkan.');
    }

    public function simpanMassal(ImporSoalMassalRequest $request): RedirectResponse
    {
        $diimpor = $this->layananSoal->simpanMassal($request->validated('soal'));

        return back()->with('success', "{$diimpor} soal berhasil diimpor.");
    }

    public function perbarui(SimpanSoalRequest $request, Soal $soal): RedirectResponse
    {
        $this->layananSoal->perbarui($soal, $request->validated());

        return back()->with('success', 'Soal diperbarui.');
    }

    public function hapus(Soal $soal): RedirectResponse
    {
        try {
            $this->layananSoal->hapus($soal);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Soal dihapus.');
    }

    public function imporBerkas(ImporBerkasRequest $request): RedirectResponse
    {
        try {
            $this->layananSoal->imporBerkas($request->file('file'));
        } catch (\Throwable $e) {
            Log::warning('Import soal dari file gagal', ['error' => $e->getMessage()]);

            return back()->with('error', 'Gagal mengimpor file. Pastikan format file sesuai template yang disediakan.');
        }

        return back()->with('success', 'Soal berhasil diimpor dari file.');
    }
}
