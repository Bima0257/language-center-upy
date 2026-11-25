<?php

namespace App\Modules\Ujian\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ujian\FilterUjianRequest;
use App\Http\Requests\Ujian\PerbaruiUjianRequest;
use App\Http\Requests\Ujian\SimpanUjianRequest;
use App\Models\Ujian;
use App\Modules\DataInduk\Services\LayananDataInduk;
use App\Modules\Ujian\Services\LayananUjian;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UjianController extends Controller
{
    public function __construct(
        private LayananUjian $layananUjian,
        private LayananDataInduk $layananDataInduk,
    ) {}

    public function daftar(FilterUjianRequest $request): Response
    {
        return Inertia::render('Admin/Ujian/index', $this->layananUjian->dataDaftar(
            $request->validated()['program_tes_id'] ?? null,
        ) + [
            'bankSoal' => $this->layananUjian->bankSoalAktif(),
            'programStudi' => $this->layananDataInduk->programStudiAktif(),
        ]);
    }

    public function tambah(): Response
    {
        return Inertia::render('Admin/Ujian/create', $this->layananUjian->dataTambah() + [
            'programTes' => $this->layananDataInduk->programTesAktif(),
            'bankSoal' => $this->layananUjian->bankSoalAktif(),
            'programStudi' => $this->layananDataInduk->programStudiAktif(),
        ]);
    }

    public function simpan(SimpanUjianRequest $request): RedirectResponse
    {
        $ujian = $this->layananUjian->buatDenganPart($request->validated());

        return to_route('admin.ujian.detail', $ujian)
            ->with('success', "Ujian berhasil dibuat. {$ujian->partUjian->count()} part otomatis dibuat.");
    }

    public function detail(Ujian $ujian): Response
    {
        return Inertia::render('Admin/Ujian/show', $this->layananUjian->dataDetail($ujian));
    }

    public function pratinjau(Ujian $ujian): Response
    {
        return Inertia::render('Ujian/PratinjauPengerjaan/index', $this->layananUjian->dataPratinjau($ujian) + [
            'backUrl' => route('admin.ujian.detail', $ujian),
            'manageUrl' => route('admin.ujian.detail', $ujian),
        ]);
    }

    public function sinkronkanPart(Ujian $ujian): RedirectResponse
    {
        $dibuat = $this->layananUjian->sinkronkanPart($ujian);

        return back()->with(
            'success',
            $dibuat
                ? "{$dibuat} part baru berhasil disinkronkan."
                : 'Tidak ada part baru yang perlu disinkronkan.',
        );
    }

    public function perbarui(PerbaruiUjianRequest $request, Ujian $ujian): RedirectResponse
    {
        $this->layananUjian->perbarui($ujian, $request->validated());

        return back()->with('success', 'Ujian diperbarui.');
    }

    public function hapus(Ujian $ujian): RedirectResponse
    {
        try {
            $this->layananUjian->hapus($ujian);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return to_route('admin.ujian.daftar')
            ->with('success', 'Ujian dihapus.');
    }
}
