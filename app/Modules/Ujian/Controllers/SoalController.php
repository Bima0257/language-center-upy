<?php

namespace App\Modules\Ujian\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ujian\SimpanSoalRequest;
use App\Models\Soal;
use App\Modules\Ujian\Services\LayananSoal;
use Illuminate\Http\RedirectResponse;

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
}
