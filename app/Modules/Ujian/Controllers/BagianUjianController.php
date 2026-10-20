<?php

namespace App\Modules\Ujian\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ujian\LampirkanSoalBagianRequest;
use App\Http\Requests\Ujian\PerbaruiBagianRequest;
use App\Http\Requests\Ujian\SimpanSusunanRequest;
use App\Http\Requests\Ujian\SusunUlangBagianSoalRequest;
use App\Http\Requests\Ujian\SusunUlangSoalRequest;
use App\Models\BagianUjian;
use App\Models\Ujian;
use App\Modules\Ujian\Services\LayananBagianUjian;
use Illuminate\Http\RedirectResponse;

class BagianUjianController extends Controller
{
    public function __construct(
        private LayananBagianUjian $layananBagian,
    ) {}

    public function perbarui(PerbaruiBagianRequest $request, Ujian $ujian, BagianUjian $bagianUjian): RedirectResponse
    {
        $this->layananBagian->perbarui($bagianUjian, $request->validated());

        return back()->with('success', 'Bagian ujian diperbarui.');
    }

    public function hapus(Ujian $ujian, BagianUjian $bagianUjian): RedirectResponse
    {
        $this->layananBagian->hapus($bagianUjian);

        return back()->with('success', 'Bagian ujian dihapus.');
    }

    public function lampirkanSoal(LampirkanSoalBagianRequest $request, Ujian $ujian, BagianUjian $bagianUjian): RedirectResponse
    {
        $dilampirkan = $this->layananBagian->lampirkanSoal($ujian, $bagianUjian, $request->validated('soal_ids'));

        return back()->with('success', "{$dilampirkan} soal berhasil ditambahkan ke bagian ujian.");
    }

    public function simpanSusunan(SimpanSusunanRequest $request, Ujian $ujian, BagianUjian $bagianUjian): RedirectResponse
    {
        $this->layananBagian->simpanSusunan(
            $bagianUjian,
            $request->validated('urutan_bagian_soal') ?? [],
            $request->validated('urutan_soal') ?? [],
        );

        return back()->with('success', 'Susunan bagian ujian disimpan.');
    }

    public function susunUlangSoal(SusunUlangSoalRequest $request, Ujian $ujian, BagianUjian $bagianUjian): RedirectResponse
    {
        $this->layananBagian->susunUlangSoal($bagianUjian, $request->validated('urutan'));

        return back()->with('success', 'Nomor soal diperbarui.');
    }

    public function susunUlangBagianSoal(SusunUlangBagianSoalRequest $request, Ujian $ujian, BagianUjian $bagianUjian): RedirectResponse
    {
        $this->layananBagian->susunUlangBagianSoal($bagianUjian, $request->validated('urutan'));

        return back()->with('success', 'Urutan bagian soal diperbarui dan nomor soal disusun ulang.');
    }
}
