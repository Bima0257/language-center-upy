<?php

namespace App\Modules\Penilaian\Repositories\Contracts;

use App\Models\SkemaPenilaian;
use Illuminate\Database\Eloquent\Collection;

interface SkemaPenilaianRepositoryInterface
{
    /**
     * @return Collection<int, SkemaPenilaian>
     */
    public function semuaDenganProgramTes(?string $programTesId = null): Collection;

    public function temukanDenganRelasi(string $id): ?SkemaPenilaian;

    public function temukanAktif(string $programTesId): ?SkemaPenilaian;

    public function temukanAktifUntukUpdate(string $programTesId): ?SkemaPenilaian;

    public function versiTerakhir(string $programTesId): ?SkemaPenilaian;

    public function buat(array $data): SkemaPenilaian;

    public function perbarui(SkemaPenilaian $skema, array $data): SkemaPenilaian;

    public function arsipkanSelain(string $programTesId, string $kecualiId): void;
}
