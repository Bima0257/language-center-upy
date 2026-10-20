<?php

namespace App\Modules\DataInduk\Repositories;

use App\Models\InterpretasiSkor;
use App\Modules\DataInduk\Repositories\Contracts\InterpretasiSkorRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class InterpretasiSkorRepository implements InterpretasiSkorRepositoryInterface
{
    public function semuaDenganJenisTes(?string $jenisTesId = null): Collection
    {
        return InterpretasiSkor::with('jenisTes')
            ->when($jenisTesId !== null, fn ($query) => $query->where('jenis_tes_id', $jenisTesId))
            ->orderBy('skor_minimum')
            ->get();
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
