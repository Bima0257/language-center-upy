<?php

namespace App\Modules\Ujian\Repositories;

use App\Models\Ujian;
use App\Modules\Ujian\Repositories\Contracts\UjianRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UjianRepository implements UjianRepositoryInterface
{
    public function semuaUntukDaftar(): Collection
    {
        return Ujian::withCount('bagianUjian')
            ->withExists('periodeUjian')
            ->get();
    }

    public function temukanDenganRelasi(string $id): ?Ujian
    {
        return Ujian::with('bagianUjian.bankSoal')->find($id);
    }

    public function create(array $data): Ujian
    {
        return Ujian::create($data);
    }

    public function update(Ujian $ujian, array $data): Ujian
    {
        $ujian->update($data);

        return $ujian->fresh();
    }

    public function delete(Ujian $ujian): void
    {
        $ujian->delete();
    }

    public function memilikiPeriodeUjian(Ujian $ujian): bool
    {
        return $ujian->periodeUjian()->exists();
    }

    public function all(): Collection
    {
        return Ujian::with('bagianUjian')->get();
    }

    public function semuaAktifBerurutan(): Collection
    {
        return Ujian::where('aktif', true)->orderBy('judul')->get();
    }

    public function count(): int
    {
        return Ujian::count();
    }
}
