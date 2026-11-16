<?php

namespace App\Modules\Ujian\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ujian\PerbaruiSoalPustakaRequest;
use App\Http\Requests\Ujian\SimpanSoalPustakaRequest;
use App\Models\Soal;
use App\Modules\Ujian\Services\LayananSoal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PustakaSoalController extends Controller
{
    public function __construct(
        private LayananSoal $layananSoal,
    ) {}

    public function tambah(Request $request): Response
    {
        $bankSoalTerpilih = $request->query('bank_soal_id');

        return Inertia::render('BankSoal/TambahSoal/create', $this->layananSoal->dataTambah(
            $bankSoalTerpilih,
            $this->filterKembali($request),
        ));
    }

    public function ubah(Soal $soal): Response
    {
        return Inertia::render('BankSoal/UbahSoal/edit', $this->layananSoal->dataUbah($soal));
    }

    public function daftar(Request $request): Response
    {
        return Inertia::render('BankSoal/PustakaSoal/index', $this->layananSoal->dataDaftar(
            $request->only(['program_tes_id', 'section', 'bank_soal_id', 'materi_soal_id', 'part_soal_id'])
        ));
    }

    public function simpan(SimpanSoalPustakaRequest $request): RedirectResponse
    {
        $jumlah = $this->layananSoal->simpanPustaka($request->validated(), $request);

        return to_route('pustaka-soal.daftar', $this->filterKembaliSetelahSimpan($request))
            ->with('success', "{$jumlah} soal berhasil ditambahkan ke bank soal.");
    }

    public function perbarui(PerbaruiSoalPustakaRequest $request, Soal $soal): RedirectResponse
    {
        $this->layananSoal->perbaruiPustaka($soal, $request->validated());

        return to_route('pustaka-soal.daftar', [
            'bank_soal_id' => $soal->bank_soal_id,
            'section' => $soal->section_id,
        ])->with('success', 'Soal berhasil diperbarui.');
    }

    public function hapus(Soal $soal): RedirectResponse
    {
        try {
            $this->layananSoal->hapus($soal);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Soal berhasil dihapus.');
    }

    private function filterKembali(Request $request): array
    {
        return array_filter(
            $request->only(['bank_soal_id', 'section', 'part_soal_id']),
            fn ($value) => $value !== null && $value !== ''
        );
    }

    private function filterKembaliSetelahSimpan(Request $request): array
    {
        return array_filter([
            'bank_soal_id' => $request->input('bank_soal_id'),
            'section' => $request->input('_kembali_section') ?: $request->input('soal.0.section_id'),
            'part_soal_id' => $request->input('_kembali_part_soal_id'),
        ], fn ($value) => ! empty($value));
    }
}
