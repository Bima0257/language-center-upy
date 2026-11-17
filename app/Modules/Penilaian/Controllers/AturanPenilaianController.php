<?php

namespace App\Modules\Penilaian\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Penilaian\SimpanAturanPenilaianRequest;
use App\Models\ProgramTes;
use App\Models\Section;
use App\Models\SkemaPenilaian;
use App\Modules\Penilaian\Services\LayananAturanPenilaian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AturanPenilaianController extends Controller
{
    public function __construct(
        private LayananAturanPenilaian $layananAturan,
    ) {}

    public function daftar(): Response
    {
        return Inertia::render(
            'Admin/DataInduk/AturanPenilaian/index',
            $this->layananAturan->dataDaftar(),
        );
    }

    public function detail(ProgramTes $programTes): Response
    {
        return Inertia::render(
            'Admin/DataInduk/AturanPenilaian/ProgramTes/index',
            $this->layananAturan->dataDetail($programTes),
        );
    }

    public function perbarui(
        SimpanAturanPenilaianRequest $request,
        SkemaPenilaian $skema,
        Section $section,
    ): RedirectResponse {
        try {
            $this->layananAturan->simpanAturan($skema, $section, $request->validated());
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Aturan penilaian berhasil disimpan.');
    }

    public function simulasi(Request $request, SkemaPenilaian $skema): RedirectResponse
    {
        $tervalidasi = $request->validate([
            'jumlah_benar' => ['required', 'array'],
            'jumlah_benar.*' => ['integer', 'min:0'],
        ]);

        try {
            $hasil = $this->layananAturan->simulasi($skema, $tervalidasi['jumlah_benar']);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('simulasi', $hasil);
    }
}
