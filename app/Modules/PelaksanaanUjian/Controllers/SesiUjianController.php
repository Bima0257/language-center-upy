<?php

namespace App\Modules\PelaksanaanUjian\Controllers;

use App\Enums\JenisPelanggaran;
use App\Enums\ModeUjian;
use App\Enums\StatusSesi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Penjadwalan\FilterJadwalUjianRequest;
use App\Http\Requests\Ujian\FilterRiwayatUjianRequest;
use App\Models\Pengguna;
use App\Models\Sertifikat;
use App\Models\SesiUjian;
use App\Models\SlotUjian;
use App\Models\Ujian;
use App\Modules\Keamanan\Actions\CatatPelanggaran;
use App\Modules\PelaksanaanUjian\Actions\Keaktifan;
use App\Modules\PelaksanaanUjian\Actions\MulaiSesiUjian;
use App\Modules\PelaksanaanUjian\Actions\SelesaikanPart;
use App\Modules\PelaksanaanUjian\Actions\SerahkanUjian;
use App\Modules\PelaksanaanUjian\Actions\SimpanJawaban;
use App\Modules\PelaksanaanUjian\DTOs\HasilWaktuUjian;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiUjianRepositoryInterface;
use App\Modules\PelaksanaanUjian\Services\LayananPelaksanaanUjian;
use App\Modules\PelaksanaanUjian\Services\LayananWaktuUjian;
use App\Modules\PendaftaranUjian\Services\LayananPendaftaranUjian;
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
        private SelesaikanPart $selesaikanPart,
        private SerahkanUjian $serahkanUjian,
        private Keaktifan $keaktifan,
        private CatatPelanggaran $catatPelanggaran,
        private LayananPelaksanaanUjian $layananPelaksanaan,
        private LayananPendaftaranUjian $layananPendaftaran,
        private LayananWaktuUjian $layananWaktu,
        private SesiUjianRepositoryInterface $sesiRepo,
    ) {}

    public function tersedia(): Response
    {
        /** @var Pengguna $pengguna */
        $pengguna = auth()->user();
        $programStudiId = $pengguna->loadMissing('profilPeserta')->profilPeserta?->program_studi_id;

        return Inertia::render('Ujian/Tersedia/index', [
            'ujian' => $this->layananPelaksanaan->daftarTryoutTersedia($programStudiId),
        ]);
    }

    public function riwayat(FilterRiwayatUjianRequest $request): Response
    {
        $tervalidasi = $request->validated();
        $tab = $tervalidasi['tab'] ?? 'resmi';

        /** @var Pengguna $pengguna */
        $pengguna = auth()->user();

        return Inertia::render('Profil/RiwayatUjian/index', [
            'sesiUjian' => $this->sesiRepo->paginateRiwayatUntukPengguna(
                penggunaId: $pengguna->id,
                mode: $tab === 'tryout' ? ModeUjian::TRYOUT : ModeUjian::OFFICIAL,
                pencarian: $tervalidasi['q'] ?? null,
                perHalaman: (int) ($tervalidasi['per_page'] ?? 10),
            ),
            'filters' => [
                'tab' => $tab,
            ],
        ]);
    }

    public function jadwal(FilterJadwalUjianRequest $request): Response
    {
        /** @var Pengguna $pengguna */
        $pengguna = auth()->user();

        return Inertia::render('Ujian/Jadwal/index', [
            ...$this->layananPendaftaran->dataJadwal($pengguna, $request->validated()),
        ]);
    }

    public function sertifikat(): Response
    {
        $sertifikat = Sertifikat::whereHas('sesiUjian', fn ($q) => $q->where('pengguna_id', auth()->id()))
            ->with(['sesiUjian.ujian', 'sesiUjian.slotUjian.periodeUjian.ujian'])
            ->orderByDesc('diterbitkan_pada')
            ->get();

        return Inertia::render('Ujian/Sertifikat/index', [
            'sertifikat' => $sertifikat,
        ]);
    }

    public function pemeriksaanAwal(SlotUjian $slotUjian): Response|RedirectResponse
    {
        $slotUjian->load(['periodeUjian.ujian.programTes', 'periodeUjian.ujian.programStudi']);

        /** @var Pengguna $pengguna */
        $pengguna = auth()->user();

        try {
            $this->layananPendaftaran->pastikanBolehMulai($pengguna, $slotUjian);
        } catch (\RuntimeException $exception) {
            return to_route('ujian.jadwal')->with('error', $exception->getMessage());
        }

        return Inertia::render('Ujian/PemeriksaanAwal/index', [
            'slotUjian' => $slotUjian,
        ]);
    }

    public function mulaiTryout(Ujian $ujian): RedirectResponse
    {
        try {
            $hasil = $this->mulaiSesi->mulaiTryout(
                penggunaId: auth()->id(),
                ujianId: $ujian->id,
            );
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('ujian.kerjakan', ['sesiUjian' => $hasil->sesiId]);
    }

    public function mulai(SlotUjian $slotUjian): RedirectResponse
    {
        $tervalidasi = request()->validate([
            'jenis_perangkat' => ['required', 'in:desktop,mobile,tablet'],
            'agen_pengguna_perangkat' => ['required', 'string', 'max:1000'],
            'swafoto' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.config('image.max_kb'),
                'dimensions:max_width=12000,max_height=12000',
            ],
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

    public function kerjakan(SesiUjian $sesiUjian): Response|RedirectResponse
    {
        $hasilWaktu = $this->layananWaktu->proses($sesiUjian);
        $sesiTerkini = $sesiUjian->fresh();

        if ($hasilWaktu->diserahkan || $sesiTerkini?->status === StatusSesi::SUBMITTED) {
            return to_route('dasbor')->with('success', $hasilWaktu->pesan ?? 'Ujian telah selesai dan dinilai.');
        }

        if (! $hasilWaktu->sesiAktif()
            || $sesiTerkini === null
            || $sesiTerkini->status !== StatusSesi::IN_PROGRESS) {
            return to_route('dasbor')->with('error', 'Sesi ujian sudah tidak aktif.');
        }

        $sesi = $sesiUjian->refresh()->load([
            'jawaban.soal',
            'partUjianAktif.section',
            'ujian.partUjian',
            'slotUjian.periodeUjian.ujian',
        ]);

        $sesi->jawaban->each(function ($jawaban) {
            $jawaban->soal->makeHidden('kunci_jawaban');
        });

        return Inertia::render('Ujian/Kerjakan/index', [
            'sesiUjian' => $sesi,
            'soalBank' => $this->layananPelaksanaan->soalUntukSesi($sesi),
            'waktu' => $hasilWaktu->waktu->keArray(),
        ]);
    }

    public function simpanJawaban(Request $request, SesiUjian $sesiUjian): JsonResponse
    {
        $tervalidasi = $request->validate([
            'soal_id' => ['required', 'exists:soal,id'],
            'teks_jawaban' => ['nullable', 'string'],
        ]);

        $this->simpanJawaban->execute(
            sesi: $sesiUjian,
            soalId: $tervalidasi['soal_id'],
            jawaban: $tervalidasi['teks_jawaban'] ?? null,
        );

        return response()->json(['status' => 'saved', 'timestamp' => now()->toIso8601String()]);
    }

    public function selesaikanPart(SesiUjian $sesiUjian): JsonResponse
    {
        $hasil = $this->selesaikanPart->execute($sesiUjian);

        return $this->responsWaktu($hasil);
    }

    public function selesaikanSection(Request $request, SesiUjian $sesiUjian): JsonResponse
    {
        $tervalidasi = $request->validate([
            'part_ujian_id' => ['required', 'uuid', 'exists:part_ujian,id'],
        ]);

        $hasil = $this->layananWaktu->selesaikanSection(
            $sesiUjian,
            $tervalidasi['part_ujian_id'],
        );

        return $this->responsWaktu($hasil);
    }

    public function serahkan(SesiUjian $sesiUjian): RedirectResponse
    {
        $sesiTerkini = $sesiUjian->fresh();

        if ($sesiTerkini?->status === StatusSesi::SUBMITTED) {
            return to_route('dasbor')->with('success', 'Ujian sudah disubmit sebelumnya.');
        }

        try {
            $hasil = $this->serahkanUjian->execute($sesiUjian->id);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $statusKelulusan = $hasil->lulus ? 'Lulus' : 'Tidak Lulus';
        $labelTingkat = $hasil->labelTingkat !== null ? ' — '.$hasil->labelTingkat : '';

        return to_route('dasbor')
            ->with(
                'success',
                "Ujian berhasil disubmit. Total skor: {$hasil->skorTotal}/{$hasil->skorTotalMaksimum}{$labelTingkat} ({$statusKelulusan})",
            );
    }

    public function keaktifan(SesiUjian $sesiUjian): JsonResponse
    {
        $hasil = $this->keaktifan->execute($sesiUjian->id);

        return $this->responsWaktu($hasil);
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

    private function responsWaktu(HasilWaktuUjian $hasil): JsonResponse
    {
        $data = $hasil->keArray();

        if ($hasil->diserahkan) {
            $data['redirect'] = route('dasbor');
        }

        return response()->json($data);
    }
}
