<?php

namespace App\Modules\Penilaian\Services;

class LayananInterpretasiSkor
{
    /**
     * @param  array<int, array<string, mixed>>  $interpretasi
     * @return array<string, mixed>
     */
    public function cari(array $interpretasi, float $total): array
    {
        foreach ($interpretasi as $rentang) {
            $minimum = (float) ($rentang['skor_minimum'] ?? 0);
            $maksimum = (float) ($rentang['skor_maksimum'] ?? 0);

            if ($total >= $minimum && $total <= $maksimum) {
                return $rentang;
            }
        }

        throw new \RuntimeException(
            'Total skor '.$total.' tidak masuk ke rentang interpretasi skor yang dikonfigurasi.',
        );
    }
}
