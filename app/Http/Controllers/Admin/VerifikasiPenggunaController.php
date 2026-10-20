<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pengguna\TolakVerifikasiRequest;
use App\Models\Pengguna;
use App\Modules\Pengguna\Services\LayananVerifikasiPengguna;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VerifikasiPenggunaController extends Controller
{
    public function __construct(
        private LayananVerifikasiPengguna $layananVerifikasiPengguna,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/VerifikasiPengguna', $this->layananVerifikasiPengguna->dataDaftar());
    }

    public function setujui(Pengguna $pengguna): RedirectResponse
    {
        try {
            $this->layananVerifikasiPengguna->setujui($pengguna);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$pengguna->name} berhasil diverifikasi.");
    }

    public function batalkanVerifikasi(Pengguna $pengguna): RedirectResponse
    {
        try {
            $this->layananVerifikasiPengguna->batalkanVerifikasi($pengguna);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Verifikasi {$pengguna->name} dibatalkan.");
    }

    public function tolak(TolakVerifikasiRequest $request, Pengguna $pengguna): RedirectResponse
    {
        $this->layananVerifikasiPengguna->tolak($pengguna, $request->validated('alasan'));

        return back()->with('success', "{$pengguna->name} ditolak. Foto identitas dihapus.");
    }
}
