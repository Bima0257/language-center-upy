<?php

namespace App\Modules\Penjadwalan\Repositories;

use App\Models\PeriodeUjian;
use App\Modules\Penjadwalan\Repositories\Contracts\PenjadwalanRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class PenjadwalanRepository implements PenjadwalanRepositoryInterface
{
    public function paginateSemua(int $perPage = 15): LengthAwarePaginator
    {
        return PeriodeUjian::with('ujian')
            ->withCount('slotUjian')
            ->latest('tanggal_mulai')
            ->paginate($perPage);
    }

    public function findOrFail(string $id): PeriodeUjian
    {
        return PeriodeUjian::with('ujian.bagianUjian')->findOrFail($id);
    }

    public function create(array $data): PeriodeUjian
    {
        return PeriodeUjian::create($data);
    }

    public function update(PeriodeUjian $periode, array $data): PeriodeUjian
    {
        $periode->update($data);

        return $periode->fresh();
    }

    public function delete(PeriodeUjian $periode): void
    {
        $periode->delete();
    }

    public function memilikiSlot(PeriodeUjian $periode): bool
    {
        return $periode->slotUjian()->exists();
    }

    public function memilikiTanggalTumpangTindih(string $ujianId, Carbon $tanggalMulai, Carbon $tanggalSelesai, ?string $kecualiId = null): bool
    {
        return PeriodeUjian::where('ujian_id', $ujianId)
            ->when($kecualiId, fn ($q) => $q->where('id', '!=', $kecualiId))
            ->where('tanggal_mulai', '<=', $tanggalSelesai->toDateString())
            ->where('tanggal_selesai', '>=', $tanggalMulai->toDateString())
            ->exists();
    }
}
