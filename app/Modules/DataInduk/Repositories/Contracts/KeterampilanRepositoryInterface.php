<?php

namespace App\Modules\DataInduk\Repositories\Contracts;

use App\Models\Keterampilan;
use Illuminate\Database\Eloquent\Collection;

interface KeterampilanRepositoryInterface
{
    /**
     * @return Collection<int, Keterampilan>
     */
    public function semuaBerurutan(?string $jenisTesId = null): Collection;

    /**
     * @return Collection<int, Keterampilan>
     */
    public function semuaAktifBerurutan(?string $jenisTesId = null): Collection;

    public function find(string $id): ?Keterampilan;

    public function temukanDenganKode(string $kode): ?Keterampilan;

    public function create(array $data): Keterampilan;

    public function update(Keterampilan $keterampilan, array $data): Keterampilan;

    public function delete(Keterampilan $keterampilan): void;

    public function urutanBerikutnya(string $jenisTesId): int;

    /**
     * @return Collection<int, Keterampilan>
     */
    public function temukanBanyak(array $ids, ?string $jenisTesId = null): Collection;
}
