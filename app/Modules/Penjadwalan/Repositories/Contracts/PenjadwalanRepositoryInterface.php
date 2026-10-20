<?php

namespace App\Modules\Penjadwalan\Repositories\Contracts;

use App\Models\PeriodeUjian;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

interface PenjadwalanRepositoryInterface
{
    public function paginateSemua(int $perPage = 15): LengthAwarePaginator;

    public function findOrFail(string $id): PeriodeUjian;

    public function create(array $data): PeriodeUjian;

    public function update(PeriodeUjian $periode, array $data): PeriodeUjian;

    public function delete(PeriodeUjian $periode): void;

    public function memilikiSlot(PeriodeUjian $periode): bool;

    public function memilikiTanggalTumpangTindih(string $ujianId, Carbon $tanggalMulai, Carbon $tanggalSelesai, ?string $kecualiId = null): bool;
}
