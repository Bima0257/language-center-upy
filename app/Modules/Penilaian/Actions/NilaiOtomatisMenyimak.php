<?php

namespace App\Modules\Penilaian\Actions;

use App\Models\Keterampilan;
use App\Models\SesiUjian;
use App\Models\Soal;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\JawabanRepositoryInterface;
use App\Modules\Penilaian\Services\LayananKonversiSkor;

class NilaiOtomatisMenyimak
{
    public function __construct(
        private JawabanRepositoryInterface $jawabanRepo,
        private LayananKonversiSkor $layananKonversi,
    ) {}

    public function execute(string $sesiId): float
    {
        $jawaban = $this->jawabanRepo->ambilBerdasarkanSesi($sesiId);
        $sesi = SesiUjian::findOrFail($sesiId);
        $jenisTesId = $sesi->slotUjian?->periodeUjian?->ujian?->jenis_tes_id;
        $keterampilan = Keterampilan::where('jenis_tes_id', $jenisTesId)
            ->where('nama', 'Listening')
            ->first();

        if ($keterampilan === null) {
            return 0;
        }

        $soalMenyimak = Soal::where('keterampilan_id', $keterampilan->id)
            ->whereIn('id', $jawaban->pluck('soal_id'))
            ->get();

        $benar = 0;
        $total = $soalMenyimak->count();

        foreach ($soalMenyimak as $soal) {
            $jawabanSoal = $jawaban->firstWhere('soal_id', $soal->id);
            if ($jawabanSoal) {
                $benarSoal = $jawabanSoal->teks_jawaban === $soal->kunci_jawaban;
                $jawabanSoal->update(['benar' => $benarSoal]);
                if ($benarSoal) {
                    $benar++;
                }
            }
        }

        return $this->layananKonversi->konversi($jenisTesId, $keterampilan->kode, $benar, $total);
    }
}
