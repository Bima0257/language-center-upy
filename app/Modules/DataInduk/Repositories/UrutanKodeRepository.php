<?php

namespace App\Modules\DataInduk\Repositories;

use App\Models\UrutanKode;
use App\Modules\DataInduk\Repositories\Contracts\UrutanKodeRepositoryInterface;

class UrutanKodeRepository implements UrutanKodeRepositoryInterface
{
    public function pastikanRuang(string $ruang): void
    {
        UrutanKode::firstOrCreate(
            ['ruang' => $ruang],
            ['nilai_terakhir' => 0],
        );
    }

    public function nilaiBerikutnya(string $ruang): int
    {
        $urutan = UrutanKode::where('ruang', $ruang)
            ->lockForUpdate()
            ->first();

        if ($urutan === null) {
            $urutan = UrutanKode::create([
                'ruang' => $ruang,
                'nilai_terakhir' => 0,
            ]);
        }

        $nilaiBerikutnya = $urutan->nilai_terakhir + 1;

        if ($nilaiBerikutnya > 999) {
            throw new \RuntimeException('Kapasitas kode 3 digit sudah habis.');
        }

        $urutan->update(['nilai_terakhir' => $nilaiBerikutnya]);

        return $nilaiBerikutnya;
    }
}
