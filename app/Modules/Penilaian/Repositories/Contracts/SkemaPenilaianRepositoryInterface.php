<?php

namespace App\Modules\Penilaian\Repositories\Contracts;

use App\Models\SkemaPenilaian;

interface SkemaPenilaianRepositoryInterface
{
    public function untukProgram(string $programTesId): SkemaPenilaian;

    public function temukanDenganRelasi(string $id): ?SkemaPenilaian;

    public function perbarui(SkemaPenilaian $skema, array $data): SkemaPenilaian;
}
