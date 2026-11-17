<?php

namespace App\Modules\DataInduk\Repositories\Contracts;

use App\Models\InterpretasiSkor;
use Illuminate\Database\Eloquent\Collection;

interface InterpretasiSkorRepositoryInterface
{
    public function semuaDenganProgramTes(?string $programTesId = null): Collection;

    /**
     * @return Collection<int, InterpretasiSkor>
     */
    public function semuaUntukSkema(string $skemaPenilaianId): Collection;

    public function updateOrCreate(
        string $skemaPenilaianId,
        int $skorMinimum,
        array $data,
    ): InterpretasiSkor;

    public function create(array $data): InterpretasiSkor;

    public function createMany(array $data): void;

    public function update(InterpretasiSkor $interpretasi, array $data): InterpretasiSkor;

    public function delete(InterpretasiSkor $interpretasi): void;
}
