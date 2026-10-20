<?php

namespace App\Modules\DataInduk\Repositories\Contracts;

use App\Models\InterpretasiSkor;
use Illuminate\Database\Eloquent\Collection;

interface InterpretasiSkorRepositoryInterface
{
    public function semuaDenganJenisTes(?string $jenisTesId = null): Collection;

    public function create(array $data): InterpretasiSkor;

    public function createMany(array $data): void;

    public function update(InterpretasiSkor $interpretasi, array $data): InterpretasiSkor;

    public function delete(InterpretasiSkor $interpretasi): void;
}
