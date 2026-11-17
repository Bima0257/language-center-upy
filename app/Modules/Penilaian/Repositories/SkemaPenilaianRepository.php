<?php

namespace App\Modules\Penilaian\Repositories;

use App\Models\SkemaPenilaian;
use App\Modules\Penilaian\Repositories\Contracts\SkemaPenilaianRepositoryInterface;

class SkemaPenilaianRepository implements SkemaPenilaianRepositoryInterface
{
    public function untukProgram(string $programTesId): SkemaPenilaian
    {
        return SkemaPenilaian::query()->createOrFirst(
            ['program_tes_id' => $programTesId],
            ['metode_total' => 'jumlah'],
        )->loadMissing(['aturanPenilaian.section', 'interpretasiSkor']);
    }

    public function temukanDenganRelasi(string $id): ?SkemaPenilaian
    {
        return SkemaPenilaian::with([
            'programTes',
            'aturanPenilaian.section',
            'interpretasiSkor',
        ])->find($id);
    }

    public function perbarui(SkemaPenilaian $skema, array $data): SkemaPenilaian
    {
        $skema->update($data);

        return $skema->fresh();
    }
}
