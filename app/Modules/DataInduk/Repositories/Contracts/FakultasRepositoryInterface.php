<?php

namespace App\Modules\DataInduk\Repositories\Contracts;

use App\Models\Fakultas;
use Illuminate\Database\Eloquent\Collection;

interface FakultasRepositoryInterface
{
    public function semuaDenganJumlahProgramStudi(): Collection;

    public function semuaAktifBerurutan(): Collection;

    public function create(array $data): Fakultas;

    public function update(Fakultas $fakultas, array $data): Fakultas;

    public function delete(Fakultas $fakultas): void;
}
