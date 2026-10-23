<?php

namespace App\Modules\Ujian\Repositories;

use App\Models\BankSoal;
use App\Modules\Ujian\Repositories\Contracts\BankSoalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class BankSoalRepository implements BankSoalRepositoryInterface
{
    public function semuaDenganJenisTesDanJumlah(?string $jenisTesId = null): Collection
    {
        return BankSoal::with('jenisTes')
            ->withCount(['soal', 'bagianUjian'])
            ->when($jenisTesId !== null, fn ($query) => $query->where('jenis_tes_id', $jenisTesId))
            ->orderBy('nama')
            ->get();
    }

    public function semuaAktifBerurutan(): Collection
    {
        return BankSoal::where('aktif', true)->orderBy('nama')->get();
    }

    public function semuaAktifDenganJenisTesDanJumlah(?string $jenisTesId = null): Collection
    {
        return BankSoal::with('jenisTes')
            ->withCount('soal')
            ->where('aktif', true)
            ->when($jenisTesId !== null, fn ($query) => $query->where('jenis_tes_id', $jenisTesId))
            ->orderBy('nama')
            ->get();
    }

    public function semuaAktifDenganJenisTesBerurutan(?string $jenisTesId = null): Collection
    {
        return BankSoal::with('jenisTes')
            ->where('aktif', true)
            ->when($jenisTesId !== null, fn ($query) => $query->where('jenis_tes_id', $jenisTesId))
            ->orderBy('nama')
            ->get();
    }

    public function find(string $id): ?BankSoal
    {
        return BankSoal::find($id);
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
