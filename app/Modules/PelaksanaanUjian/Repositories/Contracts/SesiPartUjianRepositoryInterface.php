<?php

namespace App\Modules\PelaksanaanUjian\Repositories\Contracts;

use App\Enums\AlasanSelesaiPart;
use App\Models\SesiPartUjian;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

interface SesiPartUjianRepositoryInterface
{
    public function mulai(
        string $sesiUjianId,
        string $partUjianId,
        int $durasiMenit,
        Carbon $dimulaiPada,
    ): SesiPartUjian;

    public function temukanAktif(string $sesiUjianId): ?SesiPartUjian;

    public function temukanAktifUntukUpdate(string $sesiUjianId): ?SesiPartUjian;

    public function selesaikan(
        SesiPartUjian $timing,
        AlasanSelesaiPart $alasan,
        Carbon $selesaiPada,
    ): SesiPartUjian;

    public function selesaikanSemuaTerbuka(
        string $sesiUjianId,
        AlasanSelesaiPart $alasan,
        Carbon $selesaiPada,
    ): int;

    /**
     * @return Collection<int, SesiPartUjian>
     */
    public function riwayatUntukSesi(string $sesiUjianId): Collection;
}
