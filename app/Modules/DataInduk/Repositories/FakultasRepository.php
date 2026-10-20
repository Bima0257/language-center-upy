<?php

namespace App\Modules\DataInduk\Repositories;

use App\Models\Fakultas;
use App\Modules\DataInduk\Repositories\Contracts\FakultasRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class FakultasRepository implements FakultasRepositoryInterface
{
    public function semuaDenganJumlahProgramStudi(): Collection
    {
        return Fakultas::withCount('programStudi')->orderBy('nama')->get();
    }

    public function semuaAktifBerurutan(): Collection
    {
        return Fakultas::where('aktif', true)->orderBy('nama')->get();
    }

    public function create(array $data): Fakultas
    {
        return Fakultas::create($data);
    }

    public function update(Fakultas $fakultas, array $data): Fakultas
    {
        $fakultas->update($data);

        return $fakultas->fresh();
    }

    public function delete(Fakultas $fakultas): void
    {
        $fakultas->delete();
    }
}
