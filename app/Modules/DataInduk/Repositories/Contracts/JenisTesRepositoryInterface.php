<?php

namespace App\Modules\DataInduk\Repositories\Contracts;

use App\Models\JenisTes;
use Illuminate\Database\Eloquent\Collection;

interface JenisTesRepositoryInterface
{
    /**
     * @return Collection<int, JenisTes>
     */
    public function semuaDenganJumlahBankSoal(): Collection;

    /**
     * @return Collection<int, JenisTes>
     */
    public function semuaBerurutan(): Collection;

    /**
     * @return Collection<int, JenisTes>
     */
    public function semuaAktifBerurutan(): Collection;

    public function find(string $id): ?JenisTes;

    public function create(array $data): JenisTes;

    public function update(JenisTes $jenisTes, array $data): JenisTes;

    public function delete(JenisTes $jenisTes): void;
}
