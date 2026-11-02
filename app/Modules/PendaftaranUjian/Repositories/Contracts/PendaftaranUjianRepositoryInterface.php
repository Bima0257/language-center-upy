<?php

namespace App\Modules\PendaftaranUjian\Repositories\Contracts;

use App\Models\PendaftaranUjian;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PendaftaranUjianRepositoryInterface
{
    public function temukan(string $id): PendaftaranUjian;

    public function temukanUntukPengguna(string $id, string $penggunaId): PendaftaranUjian;

    public function temukanUntukSlotDanPengguna(string $slotUjianId, string $penggunaId): ?PendaftaranUjian;

    public function temukanUntukUpdate(string $id): PendaftaranUjian;

    public function buat(array $data): PendaftaranUjian;

    public function perbarui(PendaftaranUjian $pendaftaran, array $data): PendaftaranUjian;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function hitungDisetujui(string $slotUjianId): int;

    public function memilikiUntukPeriodeUjian(string $periodeUjianId): bool;

    /**
     * @param  array<int, string>  $slotUjianIds
     * @return array<string, array{jumlahPendaftaran: int, pendingCount: int, approvedCount: int, rejectedCount: int, cancelledCount: int}>
     */
    public function ringkasanStatusPerSlot(array $slotUjianIds): array;

    public function memilikiAktifDalamPeriode(string $penggunaId, string $periodeUjianId, ?string $kecualiId = null): bool;

    /**
     * @param  array<int, string>  $slotUjianIds
     * @return array<string, array{id: string, status: string}>
     */
    public function statusUntukSlot(string $penggunaId, array $slotUjianIds): array;
}
