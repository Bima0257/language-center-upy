<?php

namespace App\Modules\Penjadwalan\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Penjadwalan\PerbaruiPeriodeUjianRequest;
use App\Http\Requests\Penjadwalan\PerbaruiSlotUjianRequest;
use App\Http\Requests\Penjadwalan\SimpanPeriodeUjianRequest;
use App\Http\Requests\Penjadwalan\SimpanSlotUjianRequest;
use App\Models\PeriodeUjian;
use App\Models\SlotUjian;
use App\Models\Ujian;
use App\Modules\Penjadwalan\Services\LayananPenjadwalan;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PenjadwalanController extends Controller
{
    public function __construct(
        private LayananPenjadwalan $layananPenjadwalan,
    ) {}

    public function daftar(): Response
    {
        return Inertia::render('Admin/PeriodeUjian/Daftar', $this->layananPenjadwalan->dataDaftar());
    }

    public function tambah(Ujian $ujian): Response
    {
        return Inertia::render('Admin/PeriodeUjian/Tambah', [
            'ujian' => $ujian,
        ]);
    }

    public function simpan(SimpanPeriodeUjianRequest $request, Ujian $ujian): RedirectResponse
    {
        try {
            $this->layananPenjadwalan->buat(array_merge($request->validated(), ['ujian_id' => $ujian->id]));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return to_route('admin.periode-ujian.daftar')
            ->with('success', 'Periode ujian berhasil dibuat.');
    }

    public function detail(PeriodeUjian $periodeUjian): Response
    {
        return Inertia::render('Admin/PeriodeUjian/Detail', $this->layananPenjadwalan->dataDetail($periodeUjian));
    }

    public function ubah(PeriodeUjian $periodeUjian): Response
    {
        return Inertia::render('Admin/PeriodeUjian/Ubah', [
            'periodeUjian' => $this->layananPenjadwalan->dataDetail($periodeUjian)['periodeUjian'],
        ]);
    }

    public function perbarui(PerbaruiPeriodeUjianRequest $request, PeriodeUjian $periodeUjian): RedirectResponse
    {
        try {
            $this->layananPenjadwalan->perbarui($periodeUjian, $request->validated());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Periode ujian diperbarui.');
    }

    public function hapus(PeriodeUjian $periodeUjian): RedirectResponse
    {
        try {
            $this->layananPenjadwalan->hapus($periodeUjian);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return to_route('admin.periode-ujian.daftar')
            ->with('success', 'Periode ujian dihapus.');
    }

    public function simpanSlot(SimpanSlotUjianRequest $request, PeriodeUjian $periodeUjian): RedirectResponse
    {
        try {
            $this->layananPenjadwalan->buatSlot($periodeUjian, $request->validated());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Slot ujian berhasil ditambahkan.');
    }

    public function perbaruiSlot(PerbaruiSlotUjianRequest $request, SlotUjian $slotUjian): RedirectResponse
    {
        try {
            $this->layananPenjadwalan->perbaruiSlot($slotUjian, $request->validated());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Slot ujian diperbarui.');
    }

    public function hapusSlot(SlotUjian $slotUjian): RedirectResponse
    {
        try {
            $this->layananPenjadwalan->hapusSlot($slotUjian);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Slot ujian dihapus.');
    }
}
