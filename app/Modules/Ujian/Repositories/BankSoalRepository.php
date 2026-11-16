<?php

namespace App\Modules\Ujian\Repositories;

use App\Models\BankSoal;
use App\Modules\Ujian\Repositories\Contracts\BankSoalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class BankSoalRepository implements BankSoalRepositoryInterface
{
    public function semuaDenganProgramTesDanJumlah(?string $programTesId = null): Collection
    {
        return BankSoal::with('programTes')
            ->withCount(['soal', 'partUjian'])
            ->when($programTesId !== null, fn ($query) => $query->where('program_tes_id', $programTesId))
            ->orderBy('nama')
            ->get();
    }

    public function semuaAktifBerurutan(): Collection
    {
        return BankSoal::withCount('partUjian')
            ->where('aktif', true)
            ->orderBy('nama')
            ->get();
    }

    public function semuaAktifDenganProgramTesDanJumlah(?string $programTesId = null): Collection
    {
        return BankSoal::with('programTes')
            ->withCount(['soal', 'partUjian'])
            ->where('aktif', true)
            ->when($programTesId !== null, fn ($query) => $query->where('program_tes_id', $programTesId))
            ->orderBy('nama')
            ->get();
    }

    public function semuaAktifDenganProgramTesBerurutan(?string $programTesId = null): Collection
    {
        return BankSoal::with('programTes')
            ->where('aktif', true)
            ->when($programTesId !== null, fn ($query) => $query->where('program_tes_id', $programTesId))
            ->orderBy('nama')
            ->get();
    }

    public function find(string $id): ?BankSoal
    {
        return BankSoal::withCount('partUjian')->find($id);
    }

    public function digunakanDalamUjian(string $id): bool
    {
        return BankSoal::whereKey($id)->whereHas('partUjian')->exists();
    }

    public function adaAktif(string $id): bool
    {
        return BankSoal::whereKey($id)->where('aktif', true)->exists();
    }

    public function create(array $data): BankSoal
    {
        return BankSoal::create($data);
    }

    public function update(BankSoal $bankSoal, array $data): BankSoal
    {
        $bankSoal->update($data);

        return $bankSoal->fresh();
    }

    public function delete(BankSoal $bankSoal): void
    {
        $bankSoal->delete();
    }
}
