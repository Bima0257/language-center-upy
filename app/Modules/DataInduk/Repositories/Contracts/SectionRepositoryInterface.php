<?php

namespace App\Modules\DataInduk\Repositories\Contracts;

use App\Models\Section;
use Illuminate\Database\Eloquent\Collection;

interface SectionRepositoryInterface
{
    /**
     * @return Collection<int, Section>
     */
    public function semuaBerurutan(?string $programTesId = null): Collection;

    /**
     * @return Collection<int, Section>
     */
    public function semuaAktifBerurutan(?string $programTesId = null): Collection;

    public function find(string $id): ?Section;

    public function temukanDenganKode(string $kode): ?Section;

    public function create(array $data): Section;

    public function update(Section $section, array $data): Section;

    public function delete(Section $section): void;

    public function urutanBerikutnya(string $programTesId): int;

    /**
     * @return Collection<int, Section>
     */
    public function temukanBanyak(array $ids, ?string $programTesId = null): Collection;
}
