<?php

namespace App\Modules\Ujian\Repositories\Contracts;

use App\Models\Ujian;
use Illuminate\Database\Eloquent\Collection;

interface UjianRepositoryInterface
{
    public function semuaUntukDaftar(?string $programTesId = null): Collection;

    /**
     * @return \Illuminate\Support\Collection<string, int>
     */
    public function jumlahPerProgramTes(): \Illuminate\Support\Collection;

    public function temukanDenganRelasi(string $id): ?Ujian;

    public function create(array $data): Ujian;

    public function update(Ujian $ujian, array $data): Ujian;

    public function delete(Ujian $ujian): void;

    public function memilikiPeriodeUjian(Ujian $ujian): bool;

    public function all(): Collection;

    public function semuaAktifBerurutan(?string $programTesId = null): Collection;

    /**
     * @return Collection<int, Ujian>
     */
    public function semuaAktifDenganProgramTes(): Collection;

    /**
     * @return Collection<int, Ujian>
     */
    public function semuaUntukDemo(): Collection;

    public function memilikiSoal(Ujian $ujian): bool;

    public function count(): int;
}
