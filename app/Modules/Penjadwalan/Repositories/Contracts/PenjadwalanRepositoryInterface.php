<?php

namespace App\Modules\Penjadwalan\Repositories\Contracts;

use App\Models\PeriodeUjian;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

interface PenjadwalanRepositoryInterface
{
    public function paginateSemua(int $perPage = 15): LengthAwarePaginator;

    public function findOrFail(string $id): PeriodeUjian;

    /**
     * @return Collection<int, PeriodeUjian>
     */
    public function semuaAktifDenganUjian(): Collection;

    /**
     * @return Collection<int, PeriodeUjian>
     */
    public function semuaAktifUntukPendaftaran(?string $kecualiPeriodePendaftaranId = null): Collection;

    /**
     * @return Collection<int, PeriodeUjian>
     */
    public function semuaAktifDenganUjianDanSlot(): Collection;

    public function create(array $data): PeriodeUjian;

    public function update(PeriodeUjian $periode, array $data): PeriodeUjian;

    public function delete(PeriodeUjian $periode): void;

    public function memilikiSlot(PeriodeUjian $periode): bool;

    public function memilikiSesiUntukPeriodeUjian(string $periodeUjianId): bool;

    public function memilikiTanggalTumpangTindih(string $ujianId, Carbon $tanggalMulai, Carbon $tanggalSelesai, ?string $kecualiId = null): bool;
}
