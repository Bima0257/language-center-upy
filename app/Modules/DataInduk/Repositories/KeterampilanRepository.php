<?php

namespace App\Modules\DataInduk\Repositories;

use App\Models\Keterampilan;
use App\Modules\DataInduk\Repositories\Contracts\KeterampilanRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class KeterampilanRepository implements KeterampilanRepositoryInterface
{
    /**
     * @return Collection<int, Keterampilan>
     */
    public function semuaBerurutan(?string $jenisTesId = null): Collection
    {
        return Keterampilan::when($jenisTesId !== null, fn ($query) => $query->where('jenis_tes_id', $jenisTesId))
            ->with('jenisTes')
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();
    }

    /**
     * @return Collection<int, Keterampilan>
     */
    public function semuaAktifBerurutan(?string $jenisTesId = null): Collection
    {
        return Keterampilan::where('aktif', true)
            ->when($jenisTesId !== null, fn ($query) => $query->where('jenis_tes_id', $jenisTesId))
            ->with('jenisTes')
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();
    }

    public function find(string $id): ?Keterampilan
    {
        return Keterampilan::find($id);
    }

    public function temukanDenganKode(string $kode): ?Keterampilan
    {
        return Keterampilan::where('kode', $kode)->first();
    }

    public function create(array $data): Keterampilan
    {
        return Keterampilan::create($data);
    }

    public function update(Keterampilan $keterampilan, array $data): Keterampilan
    {
        $keterampilan->update($data);

        return $keterampilan->fresh();
    }

    public function delete(Keterampilan $keterampilan): void
    {
        $keterampilan->delete();
    }

    public function urutanBerikutnya(string $jenisTesId): int
    {
        return (Keterampilan::where('jenis_tes_id', $jenisTesId)->max('urutan') ?? 0) + 1;
    }

    public function temukanBanyak(array $ids, ?string $jenisTesId = null): Collection
    {
        return Keterampilan::whereIn('id', $ids)
            ->when($jenisTesId !== null, fn ($query) => $query->where('jenis_tes_id', $jenisTesId))
            ->get();
    }
}
