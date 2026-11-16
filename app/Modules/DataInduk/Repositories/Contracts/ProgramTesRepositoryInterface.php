<?php

namespace App\Modules\DataInduk\Repositories\Contracts;

use App\Models\ProgramTes;
use Illuminate\Database\Eloquent\Collection;

interface ProgramTesRepositoryInterface
{
    /**
     * @return Collection<int, ProgramTes>
     */
    public function semuaDenganJumlahBankSoal(): Collection;

    /**
     * @return Collection<int, ProgramTes>
     */
    public function semuaBerurutan(): Collection;

    /**
     * @return Collection<int, ProgramTes>
     */
    public function semuaAktifBerurutan(): Collection;

    public function find(string $id): ?ProgramTes;

    public function create(array $data): ProgramTes;

    public function update(ProgramTes $programTes, array $data): ProgramTes;

    public function delete(ProgramTes $programTes): void;
}
