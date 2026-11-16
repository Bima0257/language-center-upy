<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataInduk\HapusPartSoalRequest;
use App\Http\Requests\DataInduk\HapusSectionRequest;
use App\Http\Requests\DataInduk\PerbaruiFakultasRequest;
use App\Http\Requests\DataInduk\PerbaruiPartSoalRequest;
use App\Http\Requests\DataInduk\PerbaruiProgramStudiRequest;
use App\Http\Requests\DataInduk\PerbaruiProgramTesRequest;
use App\Http\Requests\DataInduk\PerbaruiSectionRequest;
use App\Http\Requests\DataInduk\SimpanFakultasRequest;
use App\Http\Requests\DataInduk\SimpanPartSoalRequest;
use App\Http\Requests\DataInduk\SimpanProgramStudiMassalRequest;
use App\Http\Requests\DataInduk\SimpanProgramStudiRequest;
use App\Http\Requests\DataInduk\SimpanProgramTesRequest;
use App\Http\Requests\DataInduk\SimpanSectionRequest;
use App\Http\Requests\DataInduk\SusunUlangPartSoalRequest;
use App\Http\Requests\DataInduk\SusunUlangSectionRequest;
use App\Models\Fakultas;
use App\Models\PartSoal;
use App\Models\ProgramStudi;
use App\Models\ProgramTes;
use App\Models\Section;
use App\Modules\DataInduk\Services\LayananDataInduk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DataIndukController extends Controller
{
    public function __construct(
        private LayananDataInduk $layananDataInduk,
    ) {}

    public function daftarSection(): Response
    {
        return Inertia::render('Admin/DataInduk/Section/index', $this->layananDataInduk->dataDaftarSection());
    }

    public function daftarSectionPerProgramTes(ProgramTes $programTes): Response
    {
        return Inertia::render(
            'Admin/DataInduk/Section/ProgramTes/index',
            $this->layananDataInduk->dataDaftarSectionPerProgramTes($programTes),
        );
    }

    public function simpanSection(SimpanSectionRequest $request): RedirectResponse
    {
        $this->layananDataInduk->buatSection($request->validated());

        return back()->with('success', 'Section berhasil ditambahkan.');
    }

    public function perbaruiSection(PerbaruiSectionRequest $request, Section $section): RedirectResponse
    {
        $this->layananDataInduk->perbaruiSection($section, $request->validated());

        return back()->with('success', 'Section berhasil diperbarui.');
    }

    public function hapusSection(HapusSectionRequest $request, Section $section): RedirectResponse
    {
        try {
            $this->layananDataInduk->hapusSection(
                $section,
                $request->validated()['bank_soal_konteks_id'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Section berhasil dihapus.');
    }

    public function susunUlangSection(SusunUlangSectionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->layananDataInduk->susunUlangSection($validated['section'], $validated['program_tes_id']);

        return back()->with('success', 'Urutan section disimpan.');
    }

    public function daftarProgramTes(): Response
    {
        return Inertia::render('Admin/DataInduk/ProgramTes/index', $this->layananDataInduk->dataDaftarProgramTes());
    }

    public function simpanProgramTes(SimpanProgramTesRequest $request): RedirectResponse
    {
        $this->layananDataInduk->buatProgramTes($request->validated());

        return back()->with('success', 'Program tes berhasil ditambahkan.');
    }

    public function perbaruiProgramTes(PerbaruiProgramTesRequest $request, ProgramTes $programTes): RedirectResponse
    {
        $this->layananDataInduk->perbaruiProgramTes($programTes, $request->validated());

        return back()->with('success', 'Program tes berhasil diperbarui.');
    }

    public function hapusProgramTes(ProgramTes $programTes): RedirectResponse
    {
        try {
            $this->layananDataInduk->hapusProgramTes($programTes);
        } catch (\RuntimeException) {
            return back()->with('error', 'Program tes tidak bisa dihapus karena masih digunakan atau terjadi konflik data.');
        }

        return back()->with('success', 'Program tes berhasil dihapus.');
    }

    public function daftarPartSoal(Request $request): Response
    {
        $bankId = $request->query('bank_id');
        $programTesId = $request->query('program_tes_id');

        return Inertia::render('Admin/DataInduk/PartSoal/index', $this->layananDataInduk->dataDaftarPartSoal($bankId, $programTesId));
    }

    public function simpanPartSoal(SimpanPartSoalRequest $request): RedirectResponse
    {
        $this->layananDataInduk->buatPartSoal($request->validated());

        return back()->with('success', 'Part soal berhasil ditambahkan.');
    }

    public function perbaruiPartSoal(PerbaruiPartSoalRequest $request, PartSoal $partSoal): RedirectResponse
    {
        $this->layananDataInduk->perbaruiPartSoal($partSoal, $request->validated());

        return back()->with('success', 'Part soal berhasil diperbarui.');
    }

    public function susunUlangPartSoal(SusunUlangPartSoalRequest $request): RedirectResponse
    {
        $this->layananDataInduk->susunUlangPartSoal($request->validated()['part_soal']);

        return back()->with('success', 'Urutan part soal disimpan.');
    }

    public function hapusPartSoal(HapusPartSoalRequest $request, PartSoal $partSoal): RedirectResponse
    {
        try {
            $this->layananDataInduk->hapusPartSoal(
                $partSoal,
                $request->validated()['bank_soal_konteks_id'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Part soal berhasil dihapus.');
    }

    public function daftarFakultas(): Response
    {
        return Inertia::render('Admin/DataInduk/Fakultas/index', $this->layananDataInduk->dataDaftarFakultas());
    }

    public function simpanFakultas(SimpanFakultasRequest $request): RedirectResponse
    {
        $this->layananDataInduk->buatFakultas($request->validated());

        return back()->with('success', 'Fakultas berhasil ditambahkan.');
    }

    public function perbaruiFakultas(PerbaruiFakultasRequest $request, Fakultas $fakultas): RedirectResponse
    {
        $this->layananDataInduk->perbaruiFakultas($fakultas, $request->validated());

        return back()->with('success', 'Fakultas berhasil diperbarui.');
    }

    public function hapusFakultas(Fakultas $fakultas): RedirectResponse
    {
        $this->layananDataInduk->hapusFakultas($fakultas);

        return back()->with('success', 'Fakultas berhasil dihapus.');
    }

    public function daftarProgramStudi(): Response
    {
        return Inertia::render('Admin/DataInduk/ProgramStudi/index', $this->layananDataInduk->dataDaftarProgramStudi());
    }

    public function daftarProgramStudiPerFakultas(Fakultas $fakultas): Response
    {
        return Inertia::render('Admin/DataInduk/ProgramStudi/Fakultas/index', $this->layananDataInduk->dataDaftarProgramStudiPerFakultas($fakultas));
    }

    public function tambahProgramStudiMassal(Fakultas $fakultas): Response
    {
        return Inertia::render('Admin/DataInduk/ProgramStudi/TambahMassal/index', $this->layananDataInduk->dataTambahProgramStudiMassal($fakultas));
    }

    public function simpanProgramStudi(SimpanProgramStudiRequest $request): RedirectResponse
    {
        $this->layananDataInduk->buatProgramStudi($request->validated());

        return back()->with('success', 'Program studi berhasil ditambahkan.');
    }

    public function simpanProgramStudiMassal(SimpanProgramStudiMassalRequest $request, Fakultas $fakultas): RedirectResponse
    {
        $validated = $request->validated();
        $this->layananDataInduk->buatBanyakProgramStudi($fakultas, $validated['program_studi']);

        return redirect()
            ->route('admin.data-induk.program-studi.fakultas.daftar', $fakultas)
            ->with('success', 'Program studi berhasil ditambahkan.');
    }

    public function perbaruiProgramStudi(PerbaruiProgramStudiRequest $request, ProgramStudi $programStudi): RedirectResponse
    {
        $this->layananDataInduk->perbaruiProgramStudi($programStudi, $request->validated());

        return back()->with('success', 'Program studi berhasil diperbarui.');
    }

    public function hapusProgramStudi(ProgramStudi $programStudi): RedirectResponse
    {
        $this->layananDataInduk->hapusProgramStudi($programStudi);

        return back()->with('success', 'Program studi berhasil dihapus.');
    }
}
