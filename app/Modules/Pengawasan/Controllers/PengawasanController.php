<?php

namespace App\Modules\Pengawasan\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SesiUjian;
use App\Modules\Keamanan\Actions\HentikanSesi;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiUjianRepositoryInterface;
use App\Modules\Pengawasan\Actions\TinjauSesi;
use App\Modules\Pengawasan\DTOs\DataKeputusanTinjauan;
use App\Modules\Pengawasan\Services\LayananPengawasan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PengawasanController extends Controller
{
    public function __construct(
        private LayananPengawasan $layananPengawasan,
        private TinjauSesi $tinjauSesi,
        private HentikanSesi $hentikanSesi,
        private SesiUjianRepositoryInterface $sesiRepo,
    ) {}

    public function dasbor(): Response
    {
        return Inertia::render('Pengawas/Dasbor', [
            'sesiAktif' => $this->layananPengawasan->ambilSesiAktif(),
            'sesiDitandai' => $this->layananPengawasan->ambilSesiDitandai(),
        ]);
    }

    public function tampilkanSesi(SesiUjian $sesiUjian): Response
    {
        $sesi = $this->sesiRepo->temukanDenganJawaban($sesiUjian->id);

        return Inertia::render('Pengawas/TinjauanSesi', [
            'sesiUjian' => $sesi->loadMissing(['pengguna', 'catatanPelanggaran', 'slotUjian.periodeUjian.ujian', 'jawaban.soal']),
        ]);
    }

    public function pelanggaran(SesiUjian $sesiUjian): Response
    {
        return Inertia::render('Pengawas/TinjauanSesi', [
            'sesiUjian' => $sesiUjian->load(['pengguna', 'catatanPelanggaran']),
        ]);
    }

    public function hentikan(Request $request, SesiUjian $sesiUjian): RedirectResponse
    {
        $this->hentikanSesi->execute(
            $sesiUjian->id,
            $request->input('alasan', 'Dihentikan oleh pengawas.'),
        );

        return back()->with('success', 'Sesi ujian dihentikan.');
    }

    public function tinjau(Request $request, SesiUjian $sesiUjian): RedirectResponse
    {
        $tervalidasi = $request->validate([
            'status_tinjauan' => ['required', 'in:sah,ujian_ulang,dibatalkan'],
            'catatan_tinjauan' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->tinjauSesi->execute($sesiUjian->id, new DataKeputusanTinjauan(
            status: $tervalidasi['status_tinjauan'],
            catatan: $tervalidasi['catatan_tinjauan'] ?? null,
            peninjauId: auth()->id(),
        ));

        return back()->with('success', 'Keputusan tinjauan disimpan.');
    }

    public function sesiDitandai(): Response
    {
        return Inertia::render('Pengawas/Dasbor', [
            'sesiAktif' => collect(),
            'sesiDitandai' => $this->layananPengawasan->ambilSesiDitandai(),
        ]);
    }
}
