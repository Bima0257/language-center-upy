<?php

namespace App\Modules\DataInduk\Repositories;

use App\Models\Section;
use App\Modules\DataInduk\Repositories\Contracts\SectionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SectionRepository implements SectionRepositoryInterface
{
    /**
     * @return Collection<int, Section>
     */
    public function semuaBerurutan(?string $programTesId = null): Collection
    {
        return Section::when($programTesId !== null, fn ($query) => $query->where('program_tes_id', $programTesId))
            ->with('programTes')
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();
    }

    /**
     * @return Collection<int, Section>
     */
    public function semuaAktifBerurutan(?string $programTesId = null): Collection
    {
        return Section::where('aktif', true)
            ->when($programTesId !== null, fn ($query) => $query->where('program_tes_id', $programTesId))
            ->with('programTes')
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();
    }

    public function find(string $id): ?Section
    {
        return Section::find($id);
    }

    public function temukanDenganKode(string $kode): ?Section
    {
        return Section::where('kode', $kode)->first();
    }

    public function create(array $data): Section
    {
        return Section::create($data);
    }

    public function update(Section $section, array $data): Section
    {
        $section->update($data);

        return $section->fresh();
    }

    public function delete(Section $section): void
    {
        $section->delete();
    }

    public function urutanBerikutnya(string $programTesId): int
    {
        return (Section::where('program_tes_id', $programTesId)->max('urutan') ?? 0) + 1;
    }

    public function temukanBanyak(array $ids, ?string $programTesId = null): Collection
    {
        return Section::whereIn('id', $ids)
            ->when($programTesId !== null, fn ($query) => $query->where('program_tes_id', $programTesId))
            ->get();
    }
}
