<?php

namespace App\Modules\PendaftaranUjian\Repositories;

use App\Enums\ModeUjian;
use App\Enums\StatusPendaftaranUjian;
use App\Models\PendaftaranUjian;
use App\Modules\PendaftaranUjian\Repositories\Contracts\PendaftaranUjianRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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

    public function riwayatUntukPengguna(string $penggunaId): Collection
    {
        return PendaftaranUjian::query()
            ->select(['id', 'slot_ujian_id', 'status', 'catatan_admin', 'created_at'])
            ->with([
                'slotUjian:id,periode_ujian_id,tanggal,waktu_mulai,waktu_selesai',
                'slotUjian.periodeUjian:id,ujian_id,judul',
                'slotUjian.periodeUjian.ujian:id,judul,program_tes_id',
                'slotUjian.periodeUjian.ujian.programTes:id,kode,nama',
            ])
            ->where('pengguna_id', $penggunaId)
            ->where('status', '!=', StatusPendaftaranUjian::CANCELLED->value)
            ->latest('created_at')
            ->get();
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
            ->whereHas(
                'slotUjian.periodeUjian.ujian',
                fn (Builder $query) => $query->where('mode', ModeUjian::OFFICIAL),
            )
            ->when($filters['program_tes_id'] ?? null, fn ($query, $programTesId) => $query->whereHas(
                'slotUjian.periodeUjian.ujian',
                fn ($ujianQuery) => $ujianQuery->where('program_tes_id', $programTesId),
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
                            ->orWhereHas('profilPeserta', fn ($profileQuery) => $profileQuery
                                ->where('nomor_identitas', 'like', "%{$search}%")
                                ->orWhere('institusi', 'like', "%{$search}%"));
                    });
                });
            })
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function ringkasanStatusPerUjian(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $status = $filters['status'] ?? null;
        $statusValue = $status instanceof StatusPendaftaranUjian ? $status->value : $status;

        return PendaftaranUjian::query()
            ->join('slot_ujian', 'slot_ujian.id', '=', 'pendaftaran_ujian.slot_ujian_id')
            ->join('periode_ujian', 'periode_ujian.id', '=', 'slot_ujian.periode_ujian_id')
            ->join('ujian', 'ujian.id', '=', 'periode_ujian.ujian_id')
            ->join('program_tes', 'program_tes.id', '=', 'ujian.program_tes_id')
            ->select([
                'ujian.id as id',
                'ujian.judul as judul',
                'ujian.mode as mode',
                'ujian.program_tes_id as program_tes_id',
                'program_tes.nama as program_tes_nama',
            ])
            ->selectRaw('COUNT(pendaftaran_ujian.id) as jumlah_pendaftaran')
            ->selectRaw('COUNT(CASE WHEN pendaftaran_ujian.status = ? THEN 1 END) as pending_count', [StatusPendaftaranUjian::PENDING->value])
            ->selectRaw('COUNT(CASE WHEN pendaftaran_ujian.status = ? THEN 1 END) as approved_count', [StatusPendaftaranUjian::APPROVED->value])
            ->selectRaw('COUNT(CASE WHEN pendaftaran_ujian.status = ? THEN 1 END) as rejected_count', [StatusPendaftaranUjian::REJECTED->value])
            ->selectRaw('COUNT(CASE WHEN pendaftaran_ujian.status = ? THEN 1 END) as cancelled_count', [StatusPendaftaranUjian::CANCELLED->value])
            ->where('ujian.mode', ModeUjian::OFFICIAL)
            ->when($filters['program_tes_id'] ?? null, fn ($query, $programTesId) => $query->where(
                'ujian.program_tes_id',
                $programTesId,
            ))
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = (string) ($filters['search'] ?? '');

                $query->where(function ($query) use ($search): void {
                    $query->where('ujian.judul', 'like', "%{$search}%")
                        ->orWhere('program_tes.nama', 'like', "%{$search}%");
                });
            })
            ->when(filled($statusValue), fn ($query) => $query->havingRaw(
                'COUNT(CASE WHEN pendaftaran_ujian.status = ? THEN 1 END) > 0',
                [$statusValue],
            ))
            ->groupBy([
                'ujian.id',
                'ujian.judul',
                'ujian.mode',
                'ujian.program_tes_id',
                'program_tes.nama',
            ])
            ->orderBy('program_tes.nama')
            ->orderBy('ujian.judul')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (PendaftaranUjian $row): array => [
                'id' => (string) $row->getAttribute('id'),
                'judul' => (string) $row->getAttribute('judul'),
                'mode' => (string) $row->getAttribute('mode'),
                'programTesNama' => (string) $row->getAttribute('program_tes_nama'),
                'jumlahPendaftaran' => (int) $row->getAttribute('jumlah_pendaftaran'),
                'pendingCount' => (int) $row->getAttribute('pending_count'),
                'approvedCount' => (int) $row->getAttribute('approved_count'),
                'rejectedCount' => (int) $row->getAttribute('rejected_count'),
                'cancelledCount' => (int) $row->getAttribute('cancelled_count'),
            ]);
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
            'pengguna.profilPeserta.fakultas',
            'pengguna.profilPeserta.programStudi',
            'slotUjian.periodeUjian.ujian.programTes',
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
