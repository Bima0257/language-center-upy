<?php

namespace App\Modules\DataInduk\Repositories;

use App\Models\PartSoal;
use App\Modules\DataInduk\Repositories\Contracts\PartSoalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PartSoalRepository implements PartSoalRepositoryInterface
{
    public function find(string $id): ?PartSoal
    {
        return PartSoal::find($id);
    }

    public function semuaDenganJumlahSoal(): Collection
    {
        return PartSoal::with(['bankSoal', 'section'])
            ->withCount('soal')
            ->orderBy('bank_soal_id')
            ->orderBy('section_id')
            ->orderBy('urutan')
            ->get();
    }

    public function semuaAktifBerurutan(): Collection
    {
        return PartSoal::with(['bankSoal', 'section'])
            ->where('aktif', true)
            ->orderBy('bank_soal_id')
            ->orderBy('section_id')
            ->orderBy('urutan')
            ->get();
    }

    public function semuaAktifDenganBank(string $bankId): Collection
    {
        return PartSoal::with('section')
            ->where('bank_soal_id', $bankId)
            ->where('aktif', true)
            ->orderBy('section_id')
            ->orderBy('urutan')
            ->get();
    }

    /**
     * @return array<string>
     */
    public function idDenganBankDanSection(string $bankId, string $sectionId): array
    {
        return PartSoal::where('bank_soal_id', $bankId)
            ->where('section_id', $sectionId)
            ->pluck('id')
            ->all();
    }

    /**
     * @return array<string>
     */
    public function idBerurutanDenganBankDanSection(string $bankId, string $sectionId): array
    {
        return PartSoal::where('bank_soal_id', $bankId)
            ->where('section_id', $sectionId)
            ->orderBy('urutan')
            ->pluck('id')
            ->all();
    }

    public function create(array $data): PartSoal
    {
        return PartSoal::create($data);
    }

    public function update(PartSoal $partSoal, array $data): PartSoal
    {
        $partSoal->update($data);

        return $partSoal->fresh();
    }

    public function delete(PartSoal $partSoal): void
    {
        $partSoal->delete();
    }

    public function urutanBerikutnya(string $bankId, string $sectionId): int
    {
        return (PartSoal::where('bank_soal_id', $bankId)
            ->where('section_id', $sectionId)
            ->max('urutan') ?? 0) + 1;
    }

    public function temukanBanyak(array $ids): Collection
    {
        return PartSoal::whereIn('id', $ids)->get();
    }
}
