<?php

namespace App\Modules\DataInduk\Repositories\Contracts;

use App\Models\BagianSoal;
use Illuminate\Database\Eloquent\Collection;

interface BagianSoalRepositoryInterface
{
    public function find(string $id): ?BagianSoal;

    public function semuaDenganJumlahSoal(): Collection;

    /**
     * @return Collection<int, BagianSoal>
     */
    public function semuaAktifBerurutan(): Collection;

    /**
     * @return Collection<int, BagianSoal>
     */
    public function semuaAktifDenganBank(string $bankId): Collection;

    /**
     * @return array<string>
     */
    public function idDenganBankDanKeterampilan(string $bankId, string $keterampilanId): array;

    /**
     * @return array<string>
     */
    public function idBerurutanDenganBankDanKeterampilan(string $bankId, string $keterampilanId): array;

    public function create(array $data): BagianSoal;

    public function update(BagianSoal $bagianSoal, array $data): BagianSoal;

    public function delete(BagianSoal $bagianSoal): void;

    /**
     * Urutan berikutnya untuk (bank, skill) — max(order) + 1.
     */
    public function urutanBerikutnya(string $bankId, string $keterampilanId): int;

    /**
     * @return Collection<int, BagianSoal>
     */
    public function temukanBanyak(array $ids): Collection;
}
