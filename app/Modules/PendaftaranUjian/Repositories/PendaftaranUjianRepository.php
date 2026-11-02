<?php

namespace App\Modules\PendaftaranUjian\Repositories;

use App\Enums\StatusPendaftaranUjian;
use App\Models\PendaftaranUjian;
use App\Modules\PendaftaranUjian\Repositories\Contracts\PendaftaranUjianRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PendaftaranUjianRepository implements PendaftaranUjianRepositoryInterface
{
    public function temukan(string $id): PendaftaranUjian
    {
        return $this->queryDenganRelasi()->findOrFail($id);
    }

    public function temukanUntukPengguna(string $id, string $penggunaId): PendaftaranUjian
    {
        return $this->queryDenganRelasi()
            ->where('pengguna_id', $penggunaId)
            ->findOrFail($id);
    }

    public function temukanUntukSlotDanPengguna(string $slotUjianId, string $penggunaId): ?PendaftaranUjian
    {
        return $this->queryDenganRelasi()
            ->where('slot_ujian_id', $slotUjianId)
            ->where('pengguna_id', $penggunaId)
            ->first();
    }

    public function temukanUntukUpdate(string $id): PendaftaranUjian
    {
        return PendaftaranUjian::query()->lockForUpdate()->findOrFail($id);
    }

    public function buat(array $data): PendaftaranUjian
    {
        return PendaftaranUjian::create($data);
    }

    public function perbarui(PendaftaranUjian $pendaftaran, array $data): PendaftaranUjian
    {
        $pendaftaran->update($data);

        return $pendaftaran->fresh($this->relasi());
    }

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->queryDenganRelasi()
            ->when($filters['jenis_tes_id'] ?? null, fn ($query, $jenisTesId) => $query->whereHas(
                'slotUjian.periodeUjian.ujian',
                fn ($ujianQuery) => $ujianQuery->where('jenis_tes_id', $jenisTesId),
            ))
            ->when($filters['ujian_id'] ?? null, fn ($query, $ujianId) => $query->whereHas(
                'slotUjian.periodeUjian',
                fn ($periodeQuery) => $periodeQuery->where('ujian_id', $ujianId),
            ))
            ->when($filters['periode_ujian_id'] ?? null, fn ($query, $periodeUjianId) => $query->whereHas(
                'slotUjian',
                fn ($slotQuery) => $slotQuery->where('periode_ujian_id', $periodeUjianId),
            ))
            ->when($filters['slot_ujian_id'] ?? null, fn ($query, $slotUjianId) => $query->where('slot_ujian_id', $slotUjianId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, function ($query, $search): void {
                $query->whereHas('pengguna', function ($penggunaQuery) use ($search): void {
                    $penggunaQuery->where(function ($query) use ($search): void {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhereHas('profilMahasiswa', fn ($profileQuery) => $profileQuery->where('nim', 'like', "%{$search}%"));
                    });
                });
            })
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function hitungDisetujui(string $slotUjianId): int
    {
        return PendaftaranUjian::query()
            ->where('slot_ujian_id', $slotUjianId)
            ->where('status', StatusPendaftaranUjian::APPROVED)
            ->count();
    }

    public function memilikiUntukPeriodeUjian(string $periodeUjianId): bool
    {
        return PendaftaranUjian::query()
            ->whereHas('slotUjian', fn ($query) => $query->where('periode_ujian_id', $periodeUjianId))
            ->exists();
    }

    public function ringkasanStatusPerSlot(array $slotUjianIds): array
    {
        if ($slotUjianIds === []) {
            return [];
        }

        return PendaftaranUjian::query()
            ->select('slot_ujian_id')
            ->selectRaw('COUNT(*) as jumlah_pendaftaran')
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) as pending_count', [StatusPendaftaranUjian::PENDING->value])
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) as approved_count', [StatusPendaftaranUjian::APPROVED->value])
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) as rejected_count', [StatusPendaftaranUjian::REJECTED->value])
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) as cancelled_count', [StatusPendaftaranUjian::CANCELLED->value])
            ->whereIn('slot_ujian_id', $slotUjianIds)
            ->groupBy('slot_ujian_id')
            ->get()
            ->mapWithKeys(fn ($row): array => [
                $row->getAttribute('slot_ujian_id') => [
                    'jumlahPendaftaran' => (int) $row->getAttribute('jumlah_pendaftaran'),
                    'pendingCount' => (int) $row->getAttribute('pending_count'),
                    'approvedCount' => (int) $row->getAttribute('approved_count'),
                    'rejectedCount' => (int) $row->getAttribute('rejected_count'),
                    'cancelledCount' => (int) $row->getAttribute('cancelled_count'),
                ],
            ])
            ->all();
    }

    public function memilikiAktifDalamPeriode(string $penggunaId, string $periodeUjianId, ?string $kecualiId = null): bool
    {
        return PendaftaranUjian::query()
            ->where('pengguna_id', $penggunaId)
            ->whereIn('status', [
                StatusPendaftaranUjian::PENDING,
                StatusPendaftaranUjian::APPROVED,
            ])
            ->when($kecualiId, fn ($query) => $query->where('id', '!=', $kecualiId))
            ->whereHas('slotUjian', fn ($query) => $query->where('periode_ujian_id', $periodeUjianId))
            ->exists();
    }

    public function statusUntukSlot(string $penggunaId, array $slotUjianIds): array
    {
        if ($slotUjianIds === []) {
            return [];
        }

        return PendaftaranUjian::query()
            ->where('pengguna_id', $penggunaId)
            ->whereIn('slot_ujian_id', $slotUjianIds)
            ->get(['id', 'slot_ujian_id', 'status'])
            ->mapWithKeys(fn (PendaftaranUjian $pendaftaran): array => [
                $pendaftaran->slot_ujian_id => [
                    'id' => $pendaftaran->id,
                    'status' => $pendaftaran->status->value,
                ],
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function relasi(): array
    {
        return [
            'pengguna.profilMahasiswa.fakultas',
            'pengguna.profilMahasiswa.programStudi',
            'slotUjian.periodeUjian.ujian.jenisTes',
            'diprosesOleh',
        ];
    }

    /**
     * @return Builder<PendaftaranUjian>
     */
    private function queryDenganRelasi(): Builder
    {
        return PendaftaranUjian::query()->with($this->relasi());
    }
}
