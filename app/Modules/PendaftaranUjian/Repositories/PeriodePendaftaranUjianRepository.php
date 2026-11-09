<?php

namespace App\Modules\PendaftaranUjian\Repositories;

use App\Models\PeriodePendaftaranUjian;
use App\Modules\PendaftaranUjian\Repositories\Contracts\PeriodePendaftaranUjianRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PeriodePendaftaranUjianRepository implements PeriodePendaftaranUjianRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->queryDenganRelasi()
            ->when($filters['search'] ?? null, function ($query, $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('nama', 'like', "%{$search}%")
                        ->orWhereHas('periodeUjian', fn ($periodeQuery) => $periodeQuery->where('judul', 'like', "%{$search}%"));
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status): void {
                $query->where(function ($query) use ($status): void {
                    if ($status === 'inactive') {
                        $query->where('aktif', false);
                    } elseif ($status === 'upcoming') {
                        $query->where('aktif', true)->where('dibuka_pada', '>', now());
                    } elseif ($status === 'open') {
                        $query->where('aktif', true)
                            ->where('dibuka_pada', '<=', now())
                            ->where('ditutup_pada', '>=', now());
                    } elseif ($status === 'closed') {
                        $query->where(function ($query): void {
                            $query->where('aktif', false)->orWhere('ditutup_pada', '<', now());
                        });
                    }
                });
            })
            ->when($filters['jenis_tes_id'] ?? null, function ($query, $jenisTesId): void {
                $query->whereHas(
                    'periodeUjian.ujian',
                    fn ($ujianQuery) => $ujianQuery->where('jenis_tes_id', $jenisTesId),
                );
            })
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginatorKosong(int $perPage = 15): LengthAwarePaginator
    {
        return PeriodePendaftaranUjian::query()
            ->whereRaw('1 = 0')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<string, int>
     */
    public function jumlahPeriodePerJenisTes(): Collection
    {
        return PeriodePendaftaranUjian::query()
            ->join('periode_ujian', 'periode_ujian.id', '=', 'periode_pendaftaran_ujian.periode_ujian_id')
            ->join('ujian', 'ujian.id', '=', 'periode_ujian.ujian_id')
            ->select('ujian.jenis_tes_id')
            ->selectRaw('COUNT(periode_pendaftaran_ujian.id) AS jumlah_periode_pendaftaran')
            ->groupBy('ujian.jenis_tes_id')
            ->pluck('jumlah_periode_pendaftaran', 'ujian.jenis_tes_id');
    }

    public function temukan(string $id): PeriodePendaftaranUjian
    {
        return $this->queryDenganRelasi()
            ->with([
                'slotUjian' => fn ($query) => $query->withCount(['sesiUjian', 'pendaftaranUjian']),
            ])
            ->findOrFail($id);
    }

    public function temukanUntukPeriodeUjian(string $periodeUjianId, ?string $kecualiId = null): ?PeriodePendaftaranUjian
    {
        return PeriodePendaftaranUjian::query()
            ->where('periode_ujian_id', $periodeUjianId)
            ->when($kecualiId, fn ($query) => $query->where('id', '!=', $kecualiId))
            ->first();
    }

    public function buat(array $data): PeriodePendaftaranUjian
    {
        return PeriodePendaftaranUjian::create($data);
    }

    public function perbarui(PeriodePendaftaranUjian $periodePendaftaran, array $data): PeriodePendaftaranUjian
    {
        $periodePendaftaran->update($data);

        return $periodePendaftaran->fresh($this->relasi());
    }

    public function hapus(PeriodePendaftaranUjian $periodePendaftaran): void
    {
        $periodePendaftaran->delete();
    }

    /**
     * @return Builder<PeriodePendaftaranUjian>
     */
    private function queryDenganRelasi(): Builder
    {
        return PeriodePendaftaranUjian::query()->with($this->relasi())->withCount('slotUjian');
    }

    /**
     * @return array<int, string>
     */
    private function relasi(): array
    {
        return ['periodeUjian.ujian.jenisTes'];
    }
}
