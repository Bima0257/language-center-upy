<?php

namespace App\Modules\PelaksanaanUjian\Actions;

use App\Models\SesiUjian;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\JawabanRepositoryInterface;
use App\Modules\PelaksanaanUjian\Services\LayananPelaksanaanUjian;
use Illuminate\Validation\ValidationException;

class SimpanJawaban
{
    public function __construct(
        private JawabanRepositoryInterface $jawabanRepo,
        private LayananPelaksanaanUjian $layananPelaksanaan,
    ) {}

    public function execute(SesiUjian $sesi, string $soalId, ?string $jawaban): void
    {
        $soal = $this->layananPelaksanaan->soalUntukSesi($sesi)->firstWhere('id', $soalId);

        if ($soal === null) {
            throw ValidationException::withMessages([
                'soal_id' => 'Soal tidak termasuk dalam ujian ini.',
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
