<?php

namespace App\Modules\DataInduk\Repositories\Contracts;

use App\Models\ProgramStudi;
use Illuminate\Database\Eloquent\Collection;

interface ProgramStudiRepositoryInterface
{
    public function semuaDenganFakultas(): Collection;

    public function semuaDenganFakultasId(string $fakultasId): Collection;

    public function semuaAktifBerurutan(): Collection;

    public function create(array $data): ProgramStudi;

    public function createMany(array $data): void;

    public function update(ProgramStudi $programStudi, array $data): ProgramStudi;

    public function delete(ProgramStudi $programStudi): void;
}
