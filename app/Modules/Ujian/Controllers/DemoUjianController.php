<?php

namespace App\Modules\Ujian\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ujian\SimpanJawabanDemoRequest;
use App\Models\Ujian;
use App\Modules\Penilaian\Actions\HitungPenilaianDemo;
use App\Modules\Ujian\Services\LayananUjian;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DemoUjianController extends Controller
{
    public function __construct(
        private LayananUjian $layananUjian,
    ) {}

    public function daftar(): Response
    {
        return Inertia::render('Admin/DemoUjian/index', $this->layananUjian->dataDemo());
    }

    public function mulai(Ujian $ujian): Response|RedirectResponse
    {
        if (! $this->layananUjian->memilikiSoal($ujian)) {
            return to_route('admin.demo-ujian.daftar')
                ->with('error', 'Ujian ini belum memiliki soal untuk didemokan.');
        }

        return Inertia::render('Ujian/PratinjauPengerjaan/index', $this->layananUjian->dataPratinjau($ujian) + [
            'demo' => true,
            'backUrl' => route('admin.demo-ujian.daftar'),
            'manageUrl' => route('admin.ujian.detail', $ujian),
            'skorUrl' => route('admin.demo-ujian.skor', $ujian),
        ]);
    }

    public function skor(
        SimpanJawabanDemoRequest $request,
        Ujian $ujian,
        HitungPenilaianDemo $hitungPenilaianDemo,
    ): JsonResponse {
        try {
            $hasil = $hitungPenilaianDemo->execute($ujian, (array) $request->validated('jawaban'));
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'skor_section' => array_map(fn ($skor): array => [
                'section_id' => $skor->sectionId,
                'kode_section' => $skor->kodeSection,
                'nama_section' => $skor->namaSection,
                'jumlah_benar' => $skor->jumlahBenar,
                'jumlah_soal' => $skor->jumlahSoal,
                'skor_skala' => $skor->skorSkala,
            ], $hasil->skorSection),
            'total' => $hasil->total,
            'total_maksimum' => $hasil->totalMaksimum,
            'lulus' => $hasil->lulus,
            'tingkat_cefr' => $hasil->tingkatCefr,
            'label_tingkat' => $hasil->labelTingkat,
            'deskripsi_tingkat' => $hasil->deskripsiTingkat,
        ]);
    }
}
