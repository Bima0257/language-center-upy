<?php

namespace App\Modules\PendaftaranUjian\Repositories\Contracts;

use App\Models\PeriodePendaftaranUjian;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PeriodePendaftaranUjianRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function temukan(string $id): PeriodePendaftaranUjian;

    public function temukanUntukPeriodeUjian(string $periodeUjianId, ?string $kecualiId = null): ?PeriodePendaftaranUjian;

    public function buat(array $data): PeriodePendaftaranUjian;

    public function perbarui(PeriodePendaftaranUjian $periodePendaftaran, array $data): PeriodePendaftaranUjian;

    public function hapus(PeriodePendaftaranUjian $periodePendaftaran): void;
}
