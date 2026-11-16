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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function detailPerProgramTes(Request $request, ProgramTes $programTes): Response
    {
        return Inertia::render(
            'Admin/DataInduk/InterpretasiSkor/ProgramTes/index',
            $this->layananAturan->dataInterpretasi($programTes, $request->query('skema')),
        );
    }

    public function tambahMassal(Request $request, ProgramTes $programTes): Response|RedirectResponse
    {
        $data = $this->layananAturan->dataInterpretasi($programTes, $request->query('skema'));

        if ($data['skemaTerpilih'] === null || $data['readOnly']) {
            return to_route('admin.data-induk.interpretasi-skor.program-tes.daftar', $programTes)
                ->with('error', 'Pilih skema berstatus draft untuk menambah interpretasi skor.');
        }

        return Inertia::render('Admin/DataInduk/InterpretasiSkor/ProgramTes/TambahMassal/index', $data);
    }

    public function store(SimpanInterpretasiSkorRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $skema = $this->layananAturan->temukanSkema($validated['skema_penilaian_id']);
            $this->layananAturan->pastikanSkemaDraft($skema);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $validated['program_tes_id'] = $skema->program_tes_id;
        $this->layananDataInduk->buatInterpretasiSkor($validated);

        return back()->with('success', 'Interpretasi skor berhasil ditambahkan.');
    }

    public function simpanMassal(SimpanInterpretasiSkorMassalRequest $request, ProgramTes $programTes): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $skema = $this->layananAturan->temukanSkema($validated['skema_penilaian_id']);
            $this->layananAturan->pastikanSkemaDraft($skema);

            if ($skema->program_tes_id !== $programTes->id) {
                throw new \RuntimeException('Skema penilaian tidak sesuai dengan program tes.');
            }
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->layananDataInduk->buatBanyakInterpretasiSkor(
            $programTes,
            $validated['interpretasi_skor'],
            $skema->id,
        );

        return to_route('admin.data-induk.interpretasi-skor.program-tes.daftar', [
            'programTes' => $programTes->id,
            'skema' => $skema->id,
        ])->with('success', 'Interpretasi skor berhasil ditambahkan.');
    }

    public function update(PerbaruiInterpretasiSkorRequest $request, InterpretasiSkor $interpretasiSkor): RedirectResponse
    {
        $interpretasiSkor->loadMissing('skemaPenilaian');
        $skema = $interpretasiSkor->skemaPenilaian;

        if ($skema === null) {
            return back()->with('error', 'Interpretasi skor tidak terhubung ke skema penilaian.');
        }

        try {
            $this->layananAturan->pastikanSkemaDraft($skema);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $data = $request->validated();
        $data['skema_penilaian_id'] = $skema->id;
        $data['program_tes_id'] = $skema->program_tes_id;

        $this->layananDataInduk->perbaruiInterpretasiSkor($interpretasiSkor, $data);

        return back()->with('success', 'Interpretasi skor berhasil diperbarui.');
    }

    public function destroy(InterpretasiSkor $interpretasiSkor): RedirectResponse
    {
        $interpretasiSkor->loadMissing('skemaPenilaian');
        $skema = $interpretasiSkor->skemaPenilaian;

        if ($skema === null) {
            return back()->with('error', 'Interpretasi skor tidak terhubung ke skema penilaian.');
        }

        try {
            $this->layananAturan->pastikanSkemaDraft($skema);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->layananDataInduk->hapusInterpretasiSkor($interpretasiSkor);

        return back()->with('success', 'Interpretasi skor berhasil dihapus.');
    }
}
