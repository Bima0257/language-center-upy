<?php

namespace App\Modules\DataInduk\Repositories;

use App\Models\InterpretasiSkor;
use App\Modules\DataInduk\Repositories\Contracts\InterpretasiSkorRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class InterpretasiSkorRepository implements InterpretasiSkorRepositoryInterface
{
    public function semuaDenganProgramTes(?string $programTesId = null): Collection
    {
        return InterpretasiSkor::with('programTes')
            ->when($programTesId !== null, fn ($query) => $query->where('program_tes_id', $programTesId))
            ->orderBy('skor_minimum')
            ->get();
    }

    /**
     * @return Collection<int, InterpretasiSkor>
     */
    public function semuaUntukSkema(string $skemaPenilaianId): Collection
    {
        return InterpretasiSkor::with(['programTes', 'skemaPenilaian'])
            ->where('skema_penilaian_id', $skemaPenilaianId)
            ->orderBy('skor_minimum')
            ->get();
    }

    public function updateOrCreate(
        string $skemaPenilaianId,
        int $skorMinimum,
        array $data,
    ): InterpretasiSkor {
        return InterpretasiSkor::updateOrCreate(
            [
                'skema_penilaian_id' => $skemaPenilaianId,
                'skor_minimum' => $skorMinimum,
            ],
            $data + [
                'skema_penilaian_id' => $skemaPenilaianId,
                'skor_minimum' => $skorMinimum,
            ],
        );
    }

    public function create(array $data): InterpretasiSkor
    {
        return InterpretasiSkor::create($data);
    }

    public function createMany(array $data): void
    {
        foreach ($data as $interpretasi) {
            $this->create($interpretasi);
        }
    }

    public function update(InterpretasiSkor $interpretasi, array $data): InterpretasiSkor
    {
        $interpretasi->update($data);

        return $interpretasi->fresh();
    }

    public function delete(InterpretasiSkor $interpretasi): void
    {
        $interpretasi->delete();
    }
}
