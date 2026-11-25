<?php

namespace App\Modules\DataInduk\Repositories;

use App\Models\ProgramTes;
use App\Modules\DataInduk\Repositories\Contracts\ProgramTesRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ProgramTesRepository implements ProgramTesRepositoryInterface
{
    /**
     * @return Collection<int, ProgramTes>
     */
    public function semuaDenganJumlahBankSoal(): Collection
    {
        return ProgramTes::withCount([
            'bankSoal',
            'section',
            'ujian',
            'ujian as ujian_aktif_count' => fn ($query) => $query->where('aktif', true),
            'aturanPenilaian',
            'interpretasiSkor',
        ])->orderBy('nama')->get();
    }

    /**
     * @return Collection<int, ProgramTes>
     */
    public function semuaBerurutan(): Collection
    {
        return ProgramTes::orderBy('nama')->get();
    }

    /**
     * @return Collection<int, ProgramTes>
     */
    public function semuaAktifBerurutan(): Collection
    {
        return ProgramTes::where('aktif', true)->orderBy('nama')->get();
    }

    public function find(string $id): ?ProgramTes
    {
        return ProgramTes::find($id);
    }

    public function create(array $data): ProgramTes
    {
        return ProgramTes::create($data);
    }

    public function update(ProgramTes $programTes, array $data): ProgramTes
    {
        $programTes->update($data);

        return $programTes->fresh();
    }

    public function delete(ProgramTes $programTes): void
    {
        $programTes->delete();
    }
}
