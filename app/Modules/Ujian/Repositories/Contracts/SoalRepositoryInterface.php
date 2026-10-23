<?php

namespace App\Modules\Ujian\Repositories\Contracts;

use App\Models\BankSoal;
use App\Models\Soal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface SoalRepositoryInterface
{
    public function paginateDenganFilter(array $filter = [], int $perPage = 20): LengthAwarePaginator;

    public function paginatorKosong(int $perPage = 20): LengthAwarePaginator;

    public function find(string $id): ?Soal;

    public function temukanDenganRelasi(string $id): ?Soal;

    public function create(array $data): Soal;

    public function buatBanyak(array $baris): void;

    public function update(Soal $soal, array $data): Soal;

    public function delete(Soal $soal): void;

    public function urutanMaksimumMateriSoal(string $materiId): int;

    /**
     * @return Collection<int, Soal>
     */
    public function pratinjauDenganMateriSoal(string $materiId): Collection;

    /**
     * @return Collection<int, Soal>
     */
    public function soalDenganKeterampilanUntukJenisTes(string $keterampilanId, ?string $jenisTesId, ?string $bankId = null): Collection;

    /**
     * @return array<string>
     */
    public function idDalam(array $ids, string $keterampilanId, ?string $jenisTesId, ?string $bankId = null): array;

    /**
     * @return Collection<int, Soal>
     */
    public function temukanDenganIdDenganMateriSoal(array $ids): Collection;

    /**
     * Soal milik bank dan keterampilan tertentu yang belum terpasang di section.
     *
     * @param  array<string>  $kecualiIds
     * @return Collection<int, Soal>
     */
    public function soalDenganBankDanKeterampilanTanpa(string $bankId, string $keterampilanId, array $kecualiIds): Collection;

    /**
     * @param  array<string>  $keterampilanIds
     * @return Collection<int, Soal>
     */
    public function cadanganDenganKeterampilan(array $keterampilanIds, ?string $jenisTesId, ?string $bankId, array $kecualiIds): Collection;

    /**
     * @return Collection<int, BankSoal>
     */
    public function bankDenganKeterampilanDanJenisTes(string $keterampilanId, ?string $jenisTesId, ?string $bankId = null): Collection;

    public function count(): int;

    public function hitungDenganKeterampilan(): \Illuminate\Support\Collection;

    public function hitungDenganBank(): Collection;
}
