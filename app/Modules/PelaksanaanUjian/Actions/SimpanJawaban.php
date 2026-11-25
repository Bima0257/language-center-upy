<?php

namespace App\Modules\PelaksanaanUjian\Actions;

use App\Enums\StatusSesi;
use App\Models\SesiUjian;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\JawabanRepositoryInterface;
use App\Modules\PelaksanaanUjian\Services\LayananPelaksanaanUjian;
use App\Modules\PelaksanaanUjian\Services\LayananWaktuUjian;
use Illuminate\Validation\ValidationException;

class SimpanJawaban
{
    public function __construct(
        private JawabanRepositoryInterface $jawabanRepo,
        private LayananPelaksanaanUjian $layananPelaksanaan,
        private LayananWaktuUjian $layananWaktu,
    ) {}

    public function execute(SesiUjian $sesi, string $soalId, ?string $jawaban): void
    {
        $soal = $this->layananPelaksanaan->soalUntukSesi($sesi)->firstWhere('id', $soalId);

        if ($soal === null) {
            throw ValidationException::withMessages([
                'soal_id' => 'Soal tidak termasuk dalam ujian ini.',
            ]);
        }

        $waktu = $this->layananWaktu->proses($sesi);

        if (! $waktu->sesiAktif()) {
            throw ValidationException::withMessages([
                'soal_id' => 'Waktu ujian telah habis. Jawaban tidak dapat disimpan.',
            ]);
        }

        $sesi = $sesi->fresh() ?? $sesi;
        $sesi->load('partUjianAktif.section');

        if ($sesi->status !== StatusSesi::IN_PROGRESS) {
            throw ValidationException::withMessages([
                'soal_id' => 'Sesi ujian sudah tidak aktif. Jawaban tidak dapat disimpan.',
            ]);
        }

        $partAktif = $sesi->partUjianAktif;

        if ($partAktif === null || $soal->section_id !== $partAktif->section_id) {
            throw ValidationException::withMessages([
                'soal_id' => 'Soal bukan bagian dari section yang sedang dikerjakan.',
            ]);
        }

        $jawabanNormal = $jawaban === null ? null : strtoupper(trim($jawaban));
        if ($jawabanNormal === '') {
            $jawabanNormal = null;
        }

        $pilihanTersedia = collect([
            'A' => $soal->pilihan_a,
            'B' => $soal->pilihan_b,
            'C' => $soal->pilihan_c,
            'D' => $soal->pilihan_d,
        ])->filter(fn (string $pilihan): bool => trim($pilihan) !== '')->keys()->all();

        if ($jawabanNormal !== null && ! in_array($jawabanNormal, $pilihanTersedia, true)) {
            throw ValidationException::withMessages([
                'teks_jawaban' => 'Pilihan jawaban tidak valid untuk soal ini.',
            ]);
        }

        $this->jawabanRepo->updateOrCreate($sesi->id, $soal->id, [
            'teks_jawaban' => $jawabanNormal,
        ]);
    }
}
