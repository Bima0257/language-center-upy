<?php

namespace App\Modules\DataInduk\Repositories;

use App\Models\ProgramStudi;
use App\Modules\DataInduk\Repositories\Contracts\ProgramStudiRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ProgramStudiRepository implements ProgramStudiRepositoryInterface
{
    public function semuaDenganFakultas(): Collection
    {
        return ProgramStudi::with('fakultas')->orderBy('nama')->get();
    }

    public function semuaDenganFakultasId(string $fakultasId): Collection
    {
        return ProgramStudi::with('fakultas')
            ->where('fakultas_id', $fakultasId)
            ->orderBy('nama')
            ->get();
    }

    public function semuaAktifBerurutan(): Collection
    {
        return ProgramStudi::where('aktif', true)->orderBy('nama')->get();
    }

    public function create(array $data): ProgramStudi
    {
        return ProgramStudi::create($data);
    }

    public function createMany(array $data): void
    {
        foreach ($data as $programStudi) {
            $this->create($programStudi);
        }
    }

    public function update(ProgramStudi $programStudi, array $data): ProgramStudi
    {
        $programStudi->update($data);

        return $programStudi->fresh();
    }

    public function delete(ProgramStudi $programStudi): void
    {
        $programStudi->delete();
    }
}
