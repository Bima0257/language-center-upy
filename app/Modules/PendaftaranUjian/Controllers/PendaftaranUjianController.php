<?php

namespace App\Modules\PendaftaranUjian\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\PendaftaranUjian\AjukanPendaftaranUjianRequest;
use App\Http\Requests\PendaftaranUjian\FilterPendaftaranUjianRequest;
use App\Http\Requests\PendaftaranUjian\FilterPeriodePendaftaranUjianRequest;
use App\Http\Requests\PendaftaranUjian\PerbaruiPeriodePendaftaranUjianRequest;
use App\Http\Requests\PendaftaranUjian\SimpanPeriodePendaftaranUjianRequest;
use App\Http\Requests\PendaftaranUjian\TinjauPendaftaranUjianRequest;
use App\Models\PendaftaranUjian;
use App\Models\PeriodePendaftaranUjian;
use App\Models\SlotUjian;
use App\Modules\PendaftaranUjian\Services\LayananPendaftaranUjian;
use App\Modules\PendaftaranUjian\Services\LayananPeriodePendaftaranUjian;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PendaftaranUjianController extends Controller
{
    public function __construct(
        private LayananPendaftaranUjian $layananPendaftaran,
        private LayananPeriodePendaftaranUjian $layananPeriodePendaftaran,
    ) {}

    public function daftar(FilterPeriodePendaftaranUjianRequest $request): Response
    {
        return Inertia::render('Admin/PendaftaranUjian/index', $this->layananPeriodePendaftaran->dataDaftar(
            $request->validated(),
        ));
    }

    public function tambah(): Response
    {
        return Inertia::render('Admin/PendaftaranUjian/create', $this->layananPeriodePendaftaran->dataForm());
    }

    public function simpan(SimpanPeriodePendaftaranUjianRequest $request): RedirectResponse
    {
        try {
            $this->layananPeriodePendaftaran->buat($request->validated());
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('admin.pendaftaran-ujian.daftar')
            ->with('success', 'Periode pendaftaran berhasil dibuat.');
    }

    public function detail(PeriodePendaftaranUjian $periodePendaftaranUjian): Response
    {
        return Inertia::render('Admin/PendaftaranUjian/show', $this->layananPeriodePendaftaran->dataDetail(
            $periodePendaftaranUjian,
        ));
    }

    public function ubah(PeriodePendaftaranUjian $periodePendaftaranUjian): Response
    {
        return Inertia::render('Admin/PendaftaranUjian/edit', [
            'periodePendaftaranUjian' => $this->layananPeriodePendaftaran->dataDetail(
                $periodePendaftaranUjian,
            )['periodePendaftaranUjian'],
        ]);
    }

    public function perbarui(
        PerbaruiPeriodePendaftaranUjianRequest $request,
        PeriodePendaftaranUjian $periodePendaftaranUjian,
    ): RedirectResponse {
        try {
            $this->layananPeriodePendaftaran->perbarui(
                $periodePendaftaranUjian,
                $request->validated(),
            );
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Periode pendaftaran diperbarui.');
    }

    public function hapus(PeriodePendaftaranUjian $periodePendaftaranUjian): RedirectResponse
    {
        try {
            $this->layananPeriodePendaftaran->hapus($periodePendaftaranUjian);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('admin.pendaftaran-ujian.daftar')
            ->with('success', 'Periode pendaftaran dihapus.');
    }

    public function peserta(
        FilterPendaftaranUjianRequest $request,
        PeriodePendaftaranUjian $periodePendaftaranUjian,
    ): Response {
        return Inertia::render('Admin/PendaftaranUjian/peserta', $this->layananPendaftaran->dataPeserta(
            $periodePendaftaranUjian,
            $request->validated(),
        ));
    }

    public function ajukan(AjukanPendaftaranUjianRequest $request, SlotUjian $slotUjian): RedirectResponse
    {
        $buktiPembayaran = $request->file('bukti_pembayaran');

        if ($buktiPembayaran === null) {
            return back()->with('error', 'Bukti pembayaran wajib diunggah.');
        }

        try {
            $this->layananPendaftaran->ajukan(
                auth()->user(),
                $slotUjian,
                [
                    ...$request->validated(),
                    'bukti_pembayaran_nama_asli' => $buktiPembayaran->getClientOriginalName(),
                    'bukti_pembayaran_tipe_mime' => $buktiPembayaran->getMimeType(),
                    'bukti_pembayaran_ukuran' => $buktiPembayaran->getSize(),
                ],
                $buktiPembayaran,
            );
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Pendaftaran ujian berhasil dikirim dan menunggu verifikasi pembayaran.');
    }

    public function batalkan(PendaftaranUjian $pendaftaranUjian): RedirectResponse
    {
        try {
            $this->layananPendaftaran->batalkanUntukPengguna(
                $pendaftaranUjian->id,
                auth()->id(),
            );
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Pendaftaran ujian dibatalkan.');
    }

    public function setujui(TinjauPendaftaranUjianRequest $request, PendaftaranUjian $pendaftaranUjian): RedirectResponse
    {
        try {
            $this->layananPendaftaran->setujui(
                $pendaftaranUjian->id,
                auth()->id(),
                $request->validated()['catatan_admin'] ?? null,
            );
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Pendaftaran ujian disetujui.');
    }

    public function tolak(TinjauPendaftaranUjianRequest $request, PendaftaranUjian $pendaftaranUjian): RedirectResponse
    {
        try {
            $this->layananPendaftaran->tolak(
                $pendaftaranUjian->id,
                auth()->id(),
                $request->validated()['catatan_admin'] ?? null,
            );
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Pendaftaran ujian ditolak.');
    }

    public function batalkanOlehAdmin(TinjauPendaftaranUjianRequest $request, PendaftaranUjian $pendaftaranUjian): RedirectResponse
    {
        try {
            $this->layananPendaftaran->batalkanOlehAdmin(
                $pendaftaranUjian->id,
                auth()->id(),
                $request->validated()['catatan_admin'] ?? null,
            );
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Pendaftaran ujian dibatalkan oleh admin.');
    }
}
