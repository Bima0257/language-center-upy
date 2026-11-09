<?php

namespace App\Modules\Ujian\Repositories\Contracts;

use App\Models\Ujian;
use Illuminate\Database\Eloquent\Collection;

interface UjianRepositoryInterface
{
    public function semuaUntukDaftar(?string $jenisTesId = null): Collection;

    /**
     * @return \Illuminate\Support\Collection<string, int>
     */
    public function jumlahPerJenisTes(): \Illuminate\Support\Collection;

    public function temukanDenganRelasi(string $id): ?Ujian;

    public function create(array $data): Ujian;

    public function update(Ujian $ujian, array $data): Ujian;

    public function delete(Ujian $ujian): void;

    public function memilikiPeriodeUjian(Ujian $ujian): bool;

    public function all(): Collection;

    public function semuaAktifBerurutan(?string $jenisTesId = null): Collection;

    /**
     * @return Collection<int, Ujian>
     */
    public function semuaAktifDenganJenisTes(): Collection;

    public function count(): int;
}
