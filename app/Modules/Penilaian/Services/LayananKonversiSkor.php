<?php

namespace App\Modules\Penilaian\Services;

class LayananKonversiSkor
{
    /**
     * @param  array<string, mixed>  $aturan
     */
    public function konversi(array $aturan, int $jumlahBenar): float
    {
        $skorMentahMaksimum = (int) ($aturan['skor_mentah_maksimum'] ?? 0);

        if ($jumlahBenar < 0 || $jumlahBenar > $skorMentahMaksimum) {
            throw new \RuntimeException(
                "Jumlah jawaban benar ({$jumlahBenar}) berada di luar rentang tabel konversi 0-{$skorMentahMaksimum}.",
            );
        }

        $tabelKonversi = $aturan['tabel_konversi'] ?? [];

        if (! is_array($tabelKonversi)) {
            throw new \RuntimeException('Tabel konversi skor tidak valid.');
        }

        if (! array_key_exists($jumlahBenar, $tabelKonversi)
            && ! array_key_exists((string) $jumlahBenar, $tabelKonversi)) {
            throw new \RuntimeException(
                "Tabel konversi belum memiliki nilai untuk skor mentah (Raw) {$jumlahBenar}.",
            );
        }

        return (float) ($tabelKonversi[$jumlahBenar] ?? $tabelKonversi[(string) $jumlahBenar]);
    }
}
