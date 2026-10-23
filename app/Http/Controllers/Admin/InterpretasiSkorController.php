<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataInduk\PerbaruiInterpretasiSkorRequest;
use App\Http\Requests\DataInduk\SimpanInterpretasiSkorMassalRequest;
use App\Http\Requests\DataInduk\SimpanInterpretasiSkorRequest;
use App\Models\InterpretasiSkor;
use App\Models\JenisTes;
use App\Modules\DataInduk\Services\LayananDataInduk;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InterpretasiSkorController extends Controller
{
    public function __construct(
        private LayananDataInduk $layananDataInduk,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/DataInduk/InterpretasiSkor/index', $this->layananDataInduk->dataDaftarInterpretasiSkor());
    }

    public function detailPerJenisTes(JenisTes $jenisTes): Response
    {
        return Inertia::render(
            'Admin/DataInduk/InterpretasiSkor/JenisTes/index',
            $this->layananDataInduk->dataDaftarInterpretasiSkorPerJenisTes($jenisTes),
        );
    }

    public function tambahMassal(JenisTes $jenisTes): Response
    {
        return Inertia::render('Admin/DataInduk/InterpretasiSkor/JenisTes/TambahMassal/index', [
            'jenisTes' => $jenisTes,
        ]);
    }

    public function store(SimpanInterpretasiSkorRequest $request): RedirectResponse
    {
        $this->layananDataInduk->buatInterpretasiSkor($request->validated());

        return back()->with('success', 'Interpretasi skor berhasil ditambahkan.');
    }

    public function simpanMassal(SimpanInterpretasiSkorMassalRequest $request, JenisTes $jenisTes): RedirectResponse
    {
        $this->layananDataInduk->buatBanyakInterpretasiSkor(
            $jenisTes,
            $request->validated()['interpretasi_skor'],
        );

        return to_route('admin.data-induk.interpretasi-skor.jenis-tes.daftar', $jenisTes)
            ->with('success', 'Interpretasi skor berhasil ditambahkan.');
    }

    public function update(PerbaruiInterpretasiSkorRequest $request, InterpretasiSkor $interpretasiSkor): RedirectResponse
    {
        $this->layananDataInduk->perbaruiInterpretasiSkor($interpretasiSkor, $request->validated());

        return back()->with('success', 'Interpretasi skor berhasil diperbarui.');
    }

    public function destroy(InterpretasiSkor $interpretasiSkor): RedirectResponse
    {
        $this->layananDataInduk->hapusInterpretasiSkor($interpretasiSkor);

        return back()->with('success', 'Interpretasi skor berhasil dihapus.');
    }
}
