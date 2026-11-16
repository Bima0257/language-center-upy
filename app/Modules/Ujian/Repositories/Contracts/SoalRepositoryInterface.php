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

    public function digunakanDalamUjian(string $id): bool;

    public function temukanDenganRelasi(string $id): ?Soal;

    public function create(array $data): Soal;

    public function buatBanyak(array $baris): void;

    /**
     * @param  array<string>  $materiIds
     * @return Collection<int, Soal>
     */
    public function grupMateriSoal(array $materiIds, ?string $kecualiSoalId = null): Collection;

    /**
     * @return Collection<int, Soal>
     */
    public function soalUntukSection(string $sectionId): Collection;

    public function memilikiSoalDenganSection(string $sectionId): bool;

    public function update(Soal $soal, array $data): Soal;

    public function delete(Soal $soal): void;

    public function urutanMaksimumMateriSoal(string $materiId): int;

    /**
     * @return Collection<int, Soal>
     */
    public function semuaUntukPratinjauBank(string $bankId): Collection;

    /**
     * @return Collection<int, Soal>
     */
    public function soalDenganSectionUntukProgramTes(string $sectionId, ?string $programTesId, ?string $bankId = null): Collection;

    /**
     * @return Collection<int, Soal>
     */
    public function temukanDenganIdDenganMateriSoal(array $ids): Collection;

    /**
     * Soal milik bank dan section tertentu yang belum terpasang di section.
     *
     * @param  array<string>  $kecualiIds
     * @return Collection<int, Soal>
     */
    public function soalDenganBankDanSectionTanpa(string $bankId, string $sectionId, array $kecualiIds): Collection;

    /**
     * @param  array<string>  $sectionIds
     * @return Collection<int, Soal>
     */
    public function cadanganDenganSection(array $sectionIds, ?string $programTesId, ?string $bankId, array $kecualiIds): Collection;

    /**
     * @return Collection<int, BankSoal>
     */
    public function bankDenganSectionDanProgramTes(string $sectionId, ?string $programTesId, ?string $bankId = null): Collection;

    public function count(): int;

    public function hitungDenganSection(): \Illuminate\Support\Collection;

    public function hitungDenganBank(): Collection;
}
