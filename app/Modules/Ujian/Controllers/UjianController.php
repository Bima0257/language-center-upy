<?php

namespace App\Modules\Ujian\Controllers;

use App\Http\Controllers\Controller;
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

    public function daftar(): Response
    {
        return Inertia::render('Admin/Ujian/Daftar', [
            'ujian' => $this->layananUjian->dataDaftar(),
        ]);
    }

    public function tambah(): Response
    {
        return Inertia::render('Admin/Ujian/Tambah', $this->layananUjian->dataTambah() + [
            'jenisTes' => $this->layananDataInduk->jenisTesAktif(),
            'bankSoal' => $this->layananUjian->bankSoalAktif(),
        ]);
    }

    public function simpan(SimpanUjianRequest $request): RedirectResponse
    {
        $ujian = $this->layananUjian->buatDenganBagian($request->validated());

        return to_route('admin.ujian.detail', $ujian)
            ->with('success', "Ujian berhasil dibuat. {$ujian->bagianUjian->count()} bagian otomatis dibuat.");
    }

    public function detail(Ujian $ujian): Response
    {
        return Inertia::render('Admin/Ujian/Detail', $this->layananUjian->dataDetail($ujian));
    }

    public function sinkronkanBagian(Ujian $ujian): RedirectResponse
    {
        $dibuat = $this->layananUjian->sinkronkanBagian($ujian);

        return back()->with(
            'success',
            $dibuat
                ? "{$dibuat} bagian baru berhasil disinkronkan."
                : 'Tidak ada bagian baru yang perlu disinkronkan.',
        );
    }

    public function ubah(Ujian $ujian): Response
    {
        return Inertia::render('Admin/Ujian/Ubah', $this->layananUjian->dataUbah($ujian) + [
            'jenisTes' => $this->layananDataInduk->jenisTesAktif(),
        ]);
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
