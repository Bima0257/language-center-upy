<?php

namespace App\Modules\DataInduk\Repositories;

use App\Models\BagianSoal;
use App\Modules\DataInduk\Repositories\Contracts\BagianSoalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class BagianSoalRepository implements BagianSoalRepositoryInterface
{
    public function find(string $id): ?BagianSoal
    {
        return BagianSoal::find($id);
    }

    public function semuaDenganJumlahSoal(): Collection
    {
        return BagianSoal::with(['bankSoal', 'keterampilan'])
            ->withCount('soal')
            ->orderBy('bank_soal_id')
            ->orderBy('keterampilan_id')
            ->orderBy('urutan')
            ->get();
    }

    public function semuaAktifBerurutan(): Collection
    {
        return BagianSoal::with(['bankSoal', 'keterampilan'])
            ->where('aktif', true)
            ->orderBy('bank_soal_id')
            ->orderBy('keterampilan_id')
            ->orderBy('urutan')
            ->get();
    }

    public function semuaAktifDenganBank(string $bankId): Collection
    {
        return BagianSoal::with('keterampilan')
            ->where('bank_soal_id', $bankId)
            ->where('aktif', true)
            ->orderBy('keterampilan_id')
            ->orderBy('urutan')
            ->get();
    }

    /**
     * @return array<string>
     */
    public function idDenganBankDanKeterampilan(string $bankId, string $keterampilanId): array
    {
        return BagianSoal::where('bank_soal_id', $bankId)
            ->where('keterampilan_id', $keterampilanId)
            ->pluck('id')
            ->all();
    }

    /**
     * @return array<string>
     */
    public function idBerurutanDenganBankDanKeterampilan(string $bankId, string $keterampilanId): array
    {
        return BagianSoal::where('bank_soal_id', $bankId)
            ->where('keterampilan_id', $keterampilanId)
            ->orderBy('urutan')
            ->pluck('id')
            ->all();
    }

    public function create(array $data): BagianSoal
    {
        return BagianSoal::create($data);
    }

    public function update(BagianSoal $bagianSoal, array $data): BagianSoal
    {
        $bagianSoal->update($data);

        return $bagianSoal->fresh();
    }

    public function delete(BagianSoal $bagianSoal): void
    {
        $bagianSoal->delete();
    }

    public function urutanBerikutnya(string $bankId, string $keterampilanId): int
    {
        return (BagianSoal::where('bank_soal_id', $bankId)
            ->where('keterampilan_id', $keterampilanId)
            ->max('urutan') ?? 0) + 1;
    }

    public function temukanBanyak(array $ids): Collection
    {
        return BagianSoal::whereIn('id', $ids)->get();
    }
}
