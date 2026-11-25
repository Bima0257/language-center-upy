<?php

namespace App\Modules\PelaksanaanUjian\Repositories\Contracts;

use App\Enums\ModeUjian;
use App\Models\SesiUjian;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SesiUjianRepositoryInterface
{
    public function findOrFail(string $id): SesiUjian;

    public function temukanUntukUpdate(string $id): SesiUjian;

    public function temukanDenganJawaban(string $id): ?SesiUjian;

    public function temukanDenganJawabanUntukUpdate(string $id): ?SesiUjian;

    public function create(array $data): SesiUjian;

    public function update(string $id, array $data): SesiUjian;

    public function memilikiSesiAktif(string $penggunaId): bool;

    public function temukanSesiAktif(string $penggunaId): ?SesiUjian;

    public function memilikiSesiUntukPeriode(string $penggunaId, string $periodeUjianId): bool;

    public function ambilSesiAktifDenganPeriode(string $periodeUjianId): Collection;

    public function ambilSesiDitandai(bool $sertakanDitinjau = false): Collection;

    public function ambilSesiAktif(): Collection;

    public function terbaruUntukPengguna(string $penggunaId, int $batas = 3): Collection;

    public function paginateRiwayatUntukPengguna(
        string $penggunaId,
        ModeUjian $mode,
        ?string $pencarian,
        int $perHalaman = 10,
    ): LengthAwarePaginator;

    public function hitungAktif(): int;

    public function hitungDitandaiMenungguTinjauan(): int;
}
