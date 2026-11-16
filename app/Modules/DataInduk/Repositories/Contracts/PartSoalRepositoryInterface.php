<?php

namespace App\Modules\DataInduk\Repositories\Contracts;

use App\Models\PartSoal;
use Illuminate\Database\Eloquent\Collection;

interface PartSoalRepositoryInterface
{
    public function find(string $id): ?PartSoal;

    /**
     * @return Collection<int, PartSoal>
     */
    public function semuaDenganJumlahSoal(): Collection;

    /**
     * @return Collection<int, PartSoal>
     */
    public function semuaAktifBerurutan(): Collection;

    /**
     * @return Collection<int, PartSoal>
     */
    public function semuaAktifDenganBank(string $bankId): Collection;

    /**
     * @return array<string>
     */
    public function idDenganBankDanSection(string $bankId, string $sectionId): array;

    /**
     * @return array<string>
     */
    public function idBerurutanDenganBankDanSection(string $bankId, string $sectionId): array;

    public function create(array $data): PartSoal;

    public function update(PartSoal $partSoal, array $data): PartSoal;

    public function delete(PartSoal $partSoal): void;

    /**
     * Urutan berikutnya untuk (bank, section) — max(order) + 1.
     */
    public function urutanBerikutnya(string $bankId, string $sectionId): int;

    /**
     * @return Collection<int, PartSoal>
     */
    public function temukanBanyak(array $ids): Collection;
}
