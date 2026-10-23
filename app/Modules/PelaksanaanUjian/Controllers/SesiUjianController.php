<?php

namespace App\Modules\PelaksanaanUjian\Controllers;

use App\Enums\JenisPelanggaran;
use App\Enums\ModeUjian;
use App\Http\Controllers\Controller;
use App\Models\PeriodeUjian;
use App\Models\Sertifikat;
use App\Models\SesiUjian;
use App\Models\SlotUjian;
use App\Modules\Keamanan\Actions\CatatPelanggaran;
use App\Modules\PelaksanaanUjian\Actions\Keaktifan;
use App\Modules\PelaksanaanUjian\Actions\MulaiSesiUjian;
use App\Modules\PelaksanaanUjian\Actions\SelesaikanBagian;
use App\Modules\PelaksanaanUjian\Actions\SerahkanUjian;
use App\Modules\PelaksanaanUjian\Actions\SimpanJawaban;
use App\Modules\PelaksanaanUjian\Services\LayananPelaksanaanUjian;
use App\Modules\Penjadwalan\Repositories\Contracts\SlotUjianRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SesiUjianController extends Controller
{
    public function __construct(
        private MulaiSesiUjian $mulaiSesi,
        private SimpanJawaban $simpanJawaban,
        private SelesaikanBagian $selesaikanBagian,
        private SerahkanUjian $serahkanUjian,
        private Keaktifan $keaktifan,
        private CatatPelanggaran $catatPelanggaran,
        private SlotUjianRepositoryInterface $slotRepo,
        private LayananPelaksanaanUjian $layananPelaksanaan,
    ) {}

    public function tersedia(): Response
    {
        return Inertia::render('Ujian/Tersedia/index', [
            'slotUjian' => $this->slotRepo->slotTersedia(),
        ]);
    }

    public function riwayat(): Response
    {
        $sesiUjian = SesiUjian::where('pengguna_id', auth()->id())
            ->whereIn('status', ['submitted', 'terminated'])
            ->with('slotUjian.periodeUjian.ujian')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Profil/RiwayatUjian/index', [
            'sesiUjian' => $sesiUjian,
        ]);
    }

    public function jadwal(): Response
    {
        $periodeUjian = PeriodeUjian::where('aktif', true)
            ->where('tanggal_selesai', '>=', now()->toDateString())
            ->whereHas('ujian', fn ($q) => $q->where('mode', ModeUjian::OFFICIAL))
            ->with(['ujian', 'slotUjian' => fn ($q) => $q->where('aktif', true)->orderBy('tanggal')->orderBy('waktu_mulai')])
            ->orderBy('tanggal_mulai')
            ->get();

        return Inertia::render('Ujian/Jadwal/index', [
            'periodeUjian' => $periodeUjian,
        ]);
    }

    public function sertifikat(): Response
    {
        $sertifikat = Sertifikat::whereHas('sesiUjian', fn ($q) => $q->where('pengguna_id', auth()->id()))
            ->with('sesiUjian.slotUjian.periodeUjian.ujian')
            ->orderByDesc('diterbitkan_pada')
            ->get();

        return Inertia::render('Ujian/Sertifikat/index', [
            'sertifikat' => $sertifikat,
        ]);
    }

    public function pemeriksaanAwal(SlotUjian $slotUjian): Response
    {
        return Inertia::render('Ujian/PemeriksaanAwal/index', [
            'slotUjian' => $slotUjian->load('periodeUjian.ujian'),
        ]);
    }

    public function mulai(SlotUjian $slotUjian): RedirectResponse
    {
        $tervalidasi = request()->validate([
            'jenis_perangkat' => ['required', 'in:desktop,mobile,tablet'],
            'agen_pengguna_perangkat' => ['required', 'string', 'max:1000'],
            'swafoto' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        if (in_array($tervalidasi['jenis_perangkat'], ['mobile', 'tablet'])) {
            return back()->with('error', 'Ujian hanya dapat diakses dari laptop atau komputer. HP dan tablet tidak diperbolehkan.');
        }

        try {
            $hasil = $this->mulaiSesi->execute(
                penggunaId: auth()->id(),
                slotUjianId: $slotUjian->id,
                jenisPerangkat: $tervalidasi['jenis_perangkat'],
                agenPenggunaPerangkat: $tervalidasi['agen_pengguna_perangkat'],
                swafoto: request()->file('swafoto'),
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return to_route('ujian.kerjakan', ['sesiUjian' => $hasil->sesiId]);
    }

    public function kerjakan(SesiUjian $sesiUjian): Response
    {
        $sesi = $sesiUjian->load([
            'slotUjian.periodeUjian.ujian.bagianUjian',
            'jawaban.soal',
            'bagianAktif.keterampilan',
        ]);

        $sesi->jawaban->each(function ($jawaban) {
            $jawaban->soal->makeHidden('kunci_jawaban');
        });

        return Inertia::render('Ujian/Kerjakan/index', [
            'sesiUjian' => $sesi,
            'soalBank' => $this->layananPelaksanaan->soalUntukSesi($sesi),
        ]);
    }

    public function simpanJawaban(Request $request, SesiUjian $sesiUjian): JsonResponse
    {
        $tervalidasi = $request->validate([
            'soal_id' => ['required', 'exists:soal,id'],
            'teks_jawaban' => ['nullable', 'string'],
        ]);

        $this->simpanJawaban->execute(
            sesiId: $sesiUjian->id,
            soalId: $tervalidasi['soal_id'],
            jawaban: $tervalidasi['teks_jawaban'] ?? null,
        );

        return response()->json(['status' => 'saved', 'timestamp' => now()->toIso8601String()]);
    }

    public function selesaikanBagian(Request $request, SesiUjian $sesiUjian): JsonResponse
    {
        $this->selesaikanBagian->execute($sesiUjian);

        return response()->json(['status' => 'section_completed']);
    }

    public function serahkan(SesiUjian $sesiUjian): RedirectResponse
    {
        try {
            $hasil = $this->serahkanUjian->execute($sesiUjian->id);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return to_route('dasbor')
            ->with('success', "Ujian berhasil disubmit. Skor Anda: Reading {$hasil->skorMembaca}/30, Listening {$hasil->skorMenyimak}/30");
    }

    public function keaktifan(SesiUjian $sesiUjian): JsonResponse
    {
        $this->keaktifan->execute($sesiUjian->id);

        return response()->json(['status' => 'ok']);
    }

    public function catatPelanggaran(Request $request, SesiUjian $sesiUjian): JsonResponse
    {
        $tervalidasi = $request->validate([
            'jenis' => ['required', Rule::enum(JenisPelanggaran::class)],
        ]);

        $hasil = $this->catatPelanggaran->execute(
            sesiId: $sesiUjian->id,
            jenis: $tervalidasi['jenis'],
        );

        return response()->json([
            'pelanggaran' => $hasil->jumlahPelanggaran,
            'peringatan' => ! $hasil->dihentikan,
            'sesiAktif' => ! $hasil->dihentikan,
        ]);
    }
}
