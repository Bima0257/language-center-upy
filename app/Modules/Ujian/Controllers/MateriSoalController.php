<?php

namespace App\Modules\Ujian\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ujian\PerbaruiMateriSoalRequest;
use App\Http\Requests\Ujian\SimpanMateriSoalRequest;
use App\Models\MateriSoal;
use App\Modules\Ujian\Services\LayananMateriSoal;
use Illuminate\Http\RedirectResponse;

class MateriSoalController extends Controller
{
    public function __construct(
        private LayananMateriSoal $layananMateri,
    ) {}

    public function simpan(SimpanMateriSoalRequest $request): RedirectResponse
    {
        try {
            $this->layananMateri->simpan($request->validated(), $request);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Materi soal berhasil dibuat.');
    }

    public function perbarui(PerbaruiMateriSoalRequest $request, MateriSoal $materiSoal): RedirectResponse
    {
        try {
            $this->layananMateri->perbarui($materiSoal, $request->validated(), $request);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Materi soal berhasil diperbarui.');
    }

    public function hapus(MateriSoal $materiSoal): RedirectResponse
    {
        try {
            $this->layananMateri->hapus($materiSoal);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Materi soal berhasil dihapus.');
    }
}
