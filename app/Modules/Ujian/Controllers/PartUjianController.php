<?php

namespace App\Modules\Ujian\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ujian\PerbaruiPartRequest;
use App\Http\Requests\Ujian\SimpanSusunanRequest;
use App\Http\Requests\Ujian\SusunUlangPartSoalRequest;
use App\Http\Requests\Ujian\SusunUlangSoalRequest;
use App\Models\PartUjian;
use App\Models\Ujian;
use App\Modules\Ujian\Services\LayananPartUjian;
use Illuminate\Http\RedirectResponse;

class PartUjianController extends Controller
{
    public function __construct(
        private LayananPartUjian $layananPart,
    ) {}

    public function perbarui(PerbaruiPartRequest $request, Ujian $ujian, PartUjian $partUjian): RedirectResponse
    {
        $this->layananPart->perbarui($ujian, $partUjian, $request->validated());

        return back()->with('success', 'Part ujian diperbarui.');
    }

    public function hapus(Ujian $ujian, PartUjian $partUjian): RedirectResponse
    {
        $this->layananPart->hapus($ujian, $partUjian);

        return back()->with('success', 'Part ujian dihapus.');
    }

    public function simpanSusunan(SimpanSusunanRequest $request, Ujian $ujian, PartUjian $partUjian): RedirectResponse
    {
        $this->layananPart->simpanSusunan(
            $ujian,
            $partUjian,
            $request->validated('urutan_part_soal') ?? [],
            $request->validated('urutan_soal') ?? [],
        );

        return back()->with('success', 'Susunan part ujian disimpan.');
    }

    public function susunUlangSoal(SusunUlangSoalRequest $request, Ujian $ujian, PartUjian $partUjian): RedirectResponse
    {
        $this->layananPart->susunUlangSoal($ujian, $partUjian, $request->validated('urutan'));

        return back()->with('success', 'Nomor soal diperbarui.');
    }

    public function susunUlangPartSoal(SusunUlangPartSoalRequest $request, Ujian $ujian, PartUjian $partUjian): RedirectResponse
    {
        $this->layananPart->susunUlangPartSoal($ujian, $partUjian, $request->validated('urutan'));

        return back()->with('success', 'Urutan part soal diperbarui dan nomor soal disusun ulang.');
    }
}
