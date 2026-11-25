<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataInduk\PerbaruiInterpretasiSkorRequest;
use App\Http\Requests\DataInduk\SimpanInterpretasiSkorMassalRequest;
use App\Http\Requests\DataInduk\SimpanInterpretasiSkorRequest;
use App\Models\InterpretasiSkor;
use App\Models\ProgramTes;
use App\Modules\DataInduk\Services\LayananDataInduk;
use App\Modules\Penilaian\Services\LayananAturanPenilaian;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InterpretasiSkorController extends Controller
{
    public function __construct(
        private LayananDataInduk $layananDataInduk,
        private LayananAturanPenilaian $layananAturan,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/DataInduk/InterpretasiSkor/index', $this->layananDataInduk->dataDaftarInterpretasiSkor());
    }

    public function detailPerProgramTes(ProgramTes $programTes): Response
    {
        return Inertia::render(
            'Admin/DataInduk/InterpretasiSkor/ProgramTes/index',
            $this->layananAturan->dataInterpretasi($programTes),
        );
    }

    public function tambahMassal(ProgramTes $programTes): Response
    {
        return Inertia::render(
            'Admin/DataInduk/InterpretasiSkor/ProgramTes/TambahMassal/index',
            $this->layananAturan->dataInterpretasi($programTes),
        );
    }

    public function store(SimpanInterpretasiSkorRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $skema = $this->layananAturan->temukanSkema($validated['skema_penilaian_id']);
        } catch (ModelNotFoundException) {
            return back()->with('error', 'Skema penilaian tidak ditemukan.');
        }

        $validated['program_tes_id'] = $skema->program_tes_id;

        try {
            $this->layananDataInduk->buatInterpretasiSkor($validated);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Interpretasi skor berhasil ditambahkan.');
    }

    public function simpanMassal(SimpanInterpretasiSkorMassalRequest $request, ProgramTes $programTes): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $skema = $this->layananAturan->temukanSkema($validated['skema_penilaian_id']);

            if ($skema->program_tes_id !== $programTes->id) {
                throw new \RuntimeException('Skema penilaian tidak sesuai dengan program tes.');
            }
        } catch (ModelNotFoundException) {
            return back()->with('error', 'Skema penilaian tidak ditemukan.');
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        try {
            $this->layananDataInduk->buatBanyakInterpretasiSkor(
                $programTes,
                $validated['interpretasi_skor'],
                $skema->id,
            );
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('admin.data-induk.interpretasi-skor.program-tes.daftar', $programTes)
            ->with('success', 'Interpretasi skor berhasil ditambahkan.');
    }

    public function update(PerbaruiInterpretasiSkorRequest $request, InterpretasiSkor $interpretasiSkor): RedirectResponse
    {
        $interpretasiSkor->loadMissing('skemaPenilaian');
        $skema = $interpretasiSkor->skemaPenilaian;

        if ($skema === null) {
            return back()->with('error', 'Interpretasi skor tidak terhubung ke skema penilaian.');
        }

        $data = $request->validated();
        $data['skema_penilaian_id'] = $skema->id;
        $data['program_tes_id'] = $skema->program_tes_id;

        try {
            $this->layananDataInduk->perbaruiInterpretasiSkor($interpretasiSkor, $data);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Interpretasi skor berhasil diperbarui.');
    }

    public function destroy(InterpretasiSkor $interpretasiSkor): RedirectResponse
    {
        $interpretasiSkor->loadMissing('skemaPenilaian');

        if ($interpretasiSkor->skemaPenilaian === null) {
            return back()->with('error', 'Interpretasi skor tidak terhubung ke skema penilaian.');
        }

        try {
            $this->layananDataInduk->hapusInterpretasiSkor($interpretasiSkor);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Interpretasi skor berhasil dihapus.');
    }
}
