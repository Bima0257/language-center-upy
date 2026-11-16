<?php

namespace App\Modules\Penilaian\Actions;

use App\Models\Jawaban;
use App\Models\Ujian;
use App\Modules\PelaksanaanUjian\Services\LayananPelaksanaanUjian;
use App\Modules\Penilaian\DTOs\HasilPenilaianSesi;
use App\Modules\Penilaian\Services\LayananAturanPenilaian;
use Illuminate\Support\Collection;

class HitungPenilaianDemo
{
    public function __construct(
        private LayananPelaksanaanUjian $layananPelaksanaan,
        private LayananAturanPenilaian $layananAturan,
        private HitungPenilaianSesi $hitungPenilaianSesi,
    ) {}

    /**
     * @param  array<int, array{soal_id: string, jawaban: string|null}>  $jawaban
     */
    public function execute(Ujian $ujian, array $jawaban): HasilPenilaianSesi
    {
        $snapshot = $this->layananAturan->snapshotAktif($ujian->program_tes_id);
        $soalUjian = $this->layananPelaksanaan->soalUntukUjian($ujian);

        if ($soalUjian->isEmpty()) {
            throw new \RuntimeException('Ujian ini belum memiliki soal untuk dinilai.');
        }

        $jawabanPerSoal = $this->petaJawaban($jawaban);

        return $this->hitungPenilaianSesi->executeDariSoal($soalUjian, $jawabanPerSoal, $snapshot, false);
    }

    /**
     * @param  array<int, array{soal_id: string, jawaban: string|null}>  $jawaban
     * @return Collection<string, Jawaban>
     */
    private function petaJawaban(array $jawaban): Collection
    {
        $hasil = collect();

        foreach ($jawaban as $item) {
            $hasil->put($item['soal_id'], new Jawaban([
                'soal_id' => $item['soal_id'],
                'teks_jawaban' => $item['jawaban'],
            ]));
        }

        return $hasil;
    }
}
