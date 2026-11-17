<?php

namespace App\Modules\Ujian\Repositories;

use App\Models\BankSoal;
use App\Models\Section;
use App\Models\Soal;
use App\Modules\Ujian\Repositories\Contracts\SoalRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SoalRepository implements SoalRepositoryInterface
{
    public function paginateDenganFilter(array $filter = [], int $perPage = 20): LengthAwarePaginator
    {
        $sectionId = $filter['section_id'] ?? $filter['section'] ?? null;
        $bankSoalId = $filter['bank_soal_id'] ?? null;
        $materiId = $filter['materi_soal_id'] ?? null;
        $partSoalId = $filter['part_soal_id'] ?? null;

        return Soal::with(['materiSoal.mediaMateriSoal', 'bankSoal', 'section', 'partSoal'])
            ->withCount('partUjian')
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
            ->when($bankSoalId, fn ($q) => $q->where('bank_soal_id', $bankSoalId))
            ->when($materiId, fn ($q) => $q->where('materi_soal_id', $materiId))
            ->when($partSoalId, fn ($q) => $q->where('part_soal_id', $partSoalId))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginatorKosong(int $perPage = 20): LengthAwarePaginator
    {
        return Soal::query()->whereRaw('1 = 0')->paginate($perPage);
    }

    public function find(string $id): ?Soal
    {
        return Soal::find($id);
    }

    public function digunakanDalamUjian(string $id): bool
    {
        return Soal::whereKey($id)->whereHas('partUjian')->exists();
    }

    public function temukanDenganRelasi(string $id): ?Soal
    {
        return Soal::with(['materiSoal.mediaMateriSoal', 'bankSoal', 'partSoal', 'pembuat', 'pengubah'])->find($id);
    }

    public function create(array $data): Soal
    {
        return Soal::create($data);
    }

    public function buatBanyak(array $baris): void
    {
        foreach ($baris as $barisSoal) {
            Soal::create($barisSoal);
        }
    }

    public function grupMateriSoal(array $materiIds, ?string $kecualiSoalId = null): Collection
    {
        if ($materiIds === []) {
            return new Collection;
        }

        return Soal::query()
            ->select(['materi_soal_id', 'bank_soal_id', 'section_id', 'part_soal_id'])
            ->whereIn('materi_soal_id', $materiIds)
            ->when($kecualiSoalId, fn ($query) => $query->where('id', '!=', $kecualiSoalId))
            ->distinct()
            ->get();
    }

    public function soalUntukSection(string $sectionId): Collection
    {
        return Soal::with('materiSoal.mediaMateriSoal')
            ->where('section_id', $sectionId)
            ->get();
    }

    public function memilikiSoalDenganSection(string $sectionId): bool
    {
        return Soal::where('section_id', $sectionId)->exists();
    }

    public function update(Soal $soal, array $data): Soal
    {
        $soal->update($data);

        return $soal->fresh();
    }

    public function delete(Soal $soal): void
    {
        $soal->delete();
    }

    public function urutanMaksimumMateriSoal(string $materiId): int
    {
        return Soal::where('materi_soal_id', $materiId)->max('urutan') ?? 0;
    }

    public function semuaUntukPratinjauBank(string $bankId): Collection
    {
        return Soal::with(['materiSoal.mediaMateriSoal', 'section', 'partSoal'])
            ->where('bank_soal_id', $bankId)
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();
    }

    public function soalDenganSectionUntukProgramTes(string $sectionId, ?string $programTesId, ?string $bankId = null): Collection
    {
        return Soal::where('section_id', $sectionId)
            ->when($bankId, fn ($q) => $q->where('bank_soal_id', $bankId))
            ->when($programTesId, fn ($q) => $q->whereHas('bankSoal', fn ($b) => $b->where('program_tes_id', $programTesId)))
            ->orderBy('id')
            ->get();
    }

    public function temukanDenganIdDenganMateriSoal(array $ids): Collection
    {
        return Soal::with(['materiSoal.mediaMateriSoal', 'section', 'partSoal'])
            ->whereIn('id', $ids)
            ->get();
    }

    public function soalDenganBankDanSectionTanpa(string $bankId, string $sectionId, array $kecualiIds): Collection
    {
        return Soal::with(['materiSoal.mediaMateriSoal', 'bankSoal'])
            ->where('bank_soal_id', $bankId)
            ->where('section_id', $sectionId)
            ->whereNotIn('id', $kecualiIds)
            ->orderBy('id')
            ->get();
    }

    public function cadanganDenganSection(array $sectionIds, ?string $programTesId, ?string $bankId, array $kecualiIds): Collection
    {
        return Soal::with(['materiSoal.mediaMateriSoal', 'section', 'partSoal'])
            ->whereIn('section_id', $sectionIds)
            ->when($bankId, fn ($q) => $q->where('bank_soal_id', $bankId))
            ->when($programTesId, fn ($q) => $q->whereHas('bankSoal', fn ($b) => $b->where('program_tes_id', $programTesId)))
            ->whereNotIn('id', $kecualiIds)
            ->get();
    }

    public function bankDenganSectionDanProgramTes(string $sectionId, ?string $programTesId, ?string $bankId = null): Collection
    {
        $bankIds = Soal::where('section_id', $sectionId)
            ->when($programTesId, fn ($q) => $q->whereHas('bankSoal', fn ($b) => $b->where('program_tes_id', $programTesId)))
            ->when($bankId, fn ($q) => $q->where('bank_soal_id', $bankId))
            ->distinct()
            ->pluck('bank_soal_id')
            ->all();

        return BankSoal::whereIn('id', $bankIds)->orderBy('nama')->get();
    }

    public function count(): int
    {
        return Soal::count();
    }

    public function hitungDenganSection(): \Illuminate\Support\Collection
    {
        $rows = DB::select('
            SELECT section_id, COUNT(*) as total
            FROM soal
            WHERE section_id IS NOT NULL
            GROUP BY section_id
            ORDER BY total DESC
        ');

        $sectionIds = array_column($rows, 'section_id');
        $sectionMap = Section::whereIn('id', $sectionIds)->get()->keyBy('id');

        $result = new \Illuminate\Support\Collection;
        foreach ($rows as $row) {
            $section = $sectionMap->get($row->section_id);

            $result->push([
                'label' => $section->nama ?? 'Unknown',
                'count' => (int) $row->total,
            ]);
        }

        return $result;
    }

    public function hitungDenganBank(): Collection
    {
        return Soal::selectRaw('bank_soal.nama as label, COUNT(*) as count')
            ->leftJoin('bank_soal', 'bank_soal.id', '=', 'soal.bank_soal_id')
            ->groupBy('bank_soal.nama')
            ->orderByDesc('count')
            ->get();
    }
}
