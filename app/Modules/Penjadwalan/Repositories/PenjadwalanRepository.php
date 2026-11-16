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
    private const ALLOWED_PER_PAGE = [15, 25, 50, 100];

    private const ALLOWED_SORTS = ['judul', 'tanggal_mulai', 'tanggal_selesai', 'slot_ujian_count', 'aktif'];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateSemua(
        array $filters = [],
        int $perPage = 15,
        ?string $programTesId = null,
        ?string $ujianId = null,
    ): LengthAwarePaginator {
        $search = $filters['search'] ?? null;
        $aktif = $filters['aktif'] ?? null;
        $sort = $filters['sort'] ?? 'tanggal_mulai';
        $dir = ($filters['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if (! in_array($perPage, self::ALLOWED_PER_PAGE, true)) {
            $perPage = 15;
        }

        if (! in_array($sort, self::ALLOWED_SORTS, true)) {
            $sort = 'tanggal_mulai';
        }

        return PeriodeUjian::with('ujian.programTes')
            ->when($programTesId !== null, fn ($query) => $query->whereHas(
                'ujian',
                fn ($ujian) => $ujian->where('program_tes_id', $programTesId),
            ))
            ->when($ujianId !== null, fn ($query) => $query->where('ujian_id', $ujianId))
            ->when(filled($search), function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('judul', 'like', "%{$search}%")
                        ->orWhereHas('ujian', fn ($ujianQuery) => $ujianQuery->where('judul', 'like', "%{$search}%"));
                });
            })
            ->when($aktif !== null && $aktif !== '', fn ($query) => $query->where(
                'aktif',
                filter_var($aktif, FILTER_VALIDATE_BOOLEAN),
            ))
            ->withCount('slotUjian')
            ->orderBy($sort, $dir)
            ->when($sort !== 'tanggal_mulai', fn ($query) => $query->orderByDesc('tanggal_mulai'))
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginatorKosong(int $perPage = 15): LengthAwarePaginator
    {
        return PeriodeUjian::query()
            ->whereRaw('1 = 0')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return \Illuminate\Support\Collection<string, int>
     */
    public function jumlahPeriodePerProgramTes(): \Illuminate\Support\Collection
    {
        return PeriodeUjian::query()
            ->join('ujian', 'ujian.id', '=', 'periode_ujian.ujian_id')
            ->select('ujian.program_tes_id')
            ->selectRaw('COUNT(periode_ujian.id) AS jumlah_periode')
            ->groupBy('ujian.program_tes_id')
            ->pluck('jumlah_periode', 'ujian.program_tes_id');
    }

    /**
     * @return \Illuminate\Support\Collection<string, int>
     */
    public function jumlahPeriodePerUjian(?string $programTesId = null): \Illuminate\Support\Collection
    {
        return PeriodeUjian::query()
            ->join('ujian', 'ujian.id', '=', 'periode_ujian.ujian_id')
            ->when($programTesId !== null, fn ($query) => $query->where('ujian.program_tes_id', $programTesId))
            ->select('periode_ujian.ujian_id')
            ->selectRaw('COUNT(periode_ujian.id) AS jumlah_periode')
            ->groupBy('periode_ujian.ujian_id')
            ->pluck('jumlah_periode', 'periode_ujian.ujian_id');
    }

    public function findOrFail(string $id): PeriodeUjian
    {
        return PeriodeUjian::with('ujian.partUjian')->findOrFail($id);
    }

    public function semuaAktifDenganUjian(): Collection
    {
        return PeriodeUjian::with('ujian.programTes')
            ->where('aktif', true)
            ->orderByDesc('tanggal_mulai')
            ->orderBy('judul')
            ->get();
    }

    public function semuaAktifUntukPendaftaran(
        ?string $kecualiPeriodePendaftaranId = null,
        ?string $programTesId = null,
    ): Collection {
        return PeriodeUjian::query()
            ->where('aktif', true)
            ->whereHas('ujian', fn ($query) => $query->where('aktif', true))
            ->when($programTesId !== null, fn ($query) => $query->whereHas(
                'ujian',
                fn ($ujian) => $ujian->where('program_tes_id', $programTesId),
            ))
            ->whereDoesntHave('periodePendaftaran', function ($query) use ($kecualiPeriodePendaftaranId): void {
                $query->when($kecualiPeriodePendaftaranId, fn ($query) => $query->where('id', '!=', $kecualiPeriodePendaftaranId));
            })
            ->with('ujian.programTes')
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
                'ujian.programTes',
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
