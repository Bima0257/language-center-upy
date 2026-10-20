<?php

namespace App\Modules\Penilaian\Services;

use App\Models\AturanPenilaian;

class LayananKonversiSkor
{
    public function konversi(?string $jenisTesId, string $keterampilan, int $skorMentah, int $maksMentah): float
    {
        if (! $jenisTesId) {
            return 0;
        }

        $aturan = AturanPenilaian::where('jenis_tes_id', $jenisTesId)
            ->where('kode_keterampilan', $keterampilan)
            ->first();

        if (! $aturan || $maksMentah <= 0) {
            return 0;
        }

        $tabelKonversi = $aturan->tabel_konversi;

        $persentase = round($skorMentah / $maksMentah * $aturan->skor_mentah_maksimum);
        $persentase = min($persentase, $aturan->skor_mentah_maksimum);

        return (float) ($tabelKonversi[(string) $persentase] ?? 0);
    }

    public function mentahKeSkalaMembaca(string $jenisTesId, int $mentah, int $maksMentah): float
    {
        return $this->konversi($jenisTesId, 'reading', $mentah, $maksMentah);
    }

    public function mentahKeSkalaMenyimak(string $jenisTesId, int $mentah, int $maksMentah): float
    {
        return $this->konversi($jenisTesId, 'listening', $mentah, $maksMentah);
    }

    public function rataRataBerbicaraKeSkala(float $rataRata): float
    {
        return round($rataRata / 4 * 30, 1);
    }

    public function rataRataMenulisKeSkala(float $rataRata): float
    {
        return round($rataRata / 5 * 30, 1);
    }
}
