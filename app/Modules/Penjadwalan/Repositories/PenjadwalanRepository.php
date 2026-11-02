<?php

namespace App\Modules\Penjadwalan\Repositories;

use App\Enums\StatusPendaftaranUjian;
use App\Models\PeriodeUjian;
use App\Modules\Penjadwalan\Repositories\Contracts\PenjadwalanRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
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

    public function semuaAktifDenganUjian(): Collection
    {
        return PeriodeUjian::with('ujian.jenisTes')
            ->where('aktif', true)
            ->orderByDesc('tanggal_mulai')
            ->orderBy('judul')
            ->get();
    }

    public function semuaAktifUntukPendaftaran(?string $kecualiPeriodePendaftaranId = null): Collection
    {
        return PeriodeUjian::query()
            ->where('aktif', true)
            ->whereHas('ujian', fn ($query) => $query->where('aktif', true))
            ->whereDoesntHave('periodePendaftaran', function ($query) use ($kecualiPeriodePendaftaranId): void {
                $query->when($kecualiPeriodePendaftaranId, fn ($query) => $query->where('id', '!=', $kecualiPeriodePendaftaranId));
            })
            ->with('ujian.jenisTes')
            ->orderBy('tanggal_mulai')
            ->orderBy('judul')
            ->get();
    }

    public function semuaAktifDenganUjianDanSlot(): Collection
    {
        return PeriodeUjian::query()
            ->where('aktif', true)
            ->whereHas('ujian', fn ($query) => $query->where('aktif', true))
            ->with([
                'ujian.jenisTes',
                'periodePendaftaran',
                'slotUjian' => fn ($query) => $query
                    ->where('aktif', true)
                    ->withCount([
                        'pendaftaranUjian as pendaftaran_disetujui_count' => fn ($pendaftaranQuery) => $pendaftaranQuery->where(
                            'status',
                            StatusPendaftaranUjian::APPROVED,
                        ),
                    ])
                    ->orderBy('tanggal')
                    ->orderBy('waktu_mulai'),
            ])
            ->orderBy('tanggal_mulai')
            ->orderBy('judul')
            ->get();
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

    public function memilikiSesiUntukPeriodeUjian(string $periodeUjianId): bool
    {
        return PeriodeUjian::query()
            ->whereKey($periodeUjianId)
            ->whereHas('slotUjian.sesiUjian')
            ->exists();
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
