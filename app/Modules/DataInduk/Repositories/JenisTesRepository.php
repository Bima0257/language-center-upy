<?php

namespace App\Modules\DataInduk\Repositories;

use App\Models\JenisTes;
use App\Modules\DataInduk\Repositories\Contracts\JenisTesRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class JenisTesRepository implements JenisTesRepositoryInterface
{
    /**
     * @return Collection<int, JenisTes>
     */
    public function semuaDenganJumlahBankSoal(): Collection
    {
        return JenisTes::withCount([
            'bankSoal',
            'keterampilan',
            'ujian',
            'aturanPenilaian',
            'interpretasiSkor',
        ])->orderBy('nama')->get();
    }

    /**
     * @return Collection<int, JenisTes>
     */
    public function semuaBerurutan(): Collection
    {
        return JenisTes::orderBy('nama')->get();
    }

    /**
     * @return Collection<int, JenisTes>
     */
    public function semuaAktifBerurutan(): Collection
    {
        return JenisTes::where('aktif', true)->orderBy('nama')->get();
    }

    public function find(string $id): ?JenisTes
    {
        return JenisTes::find($id);
    }

    public function create(array $data): JenisTes
    {
        return JenisTes::create($data);
    }

    public function update(JenisTes $jenisTes, array $data): JenisTes
    {
        $jenisTes->update($data);

        return $jenisTes->fresh();
    }

    public function delete(JenisTes $jenisTes): void
    {
        $jenisTes->delete();
    }
}
