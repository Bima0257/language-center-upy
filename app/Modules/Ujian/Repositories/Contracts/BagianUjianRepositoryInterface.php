<?php

namespace App\Modules\Ujian\Repositories\Contracts;

use App\Models\BagianUjian;
use App\Models\SoalBagianUjian;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

interface BagianUjianRepositoryInterface
{
    public function create(array $data): BagianUjian;

    public function update(BagianUjian $bagian, array $data): BagianUjian;

    public function delete(BagianUjian $bagian): void;

    public function soalDikelompokkanDenganBagian(BaseCollection $bagianIds): BaseCollection;

    public function bagianSoalDikelompokkanDenganBagian(BaseCollection $bagianIds): BaseCollection;

    /**
     * @return Collection<int, SoalBagianUjian>
     */
    public function barisPivotDenganIdBagian(BaseCollection $bagianIds): Collection;

    public function buatAtauTemukanPivotSoal(string $bagianId, string $soalId, int $urutan): void;

    public function buatPivotSoal(string $bagianId, string $soalId, int $urutan): void;

    public function hapusPivotSoal(string $bagianId, string $soalId): void;

    /**
     * @return array<string>
     */
    public function idSoalDalamBagian(string $bagianId): array;

    public function urutanSoalBerikutnya(string $bagianId): int;

    public function perbaruiUrutanSoal(string $bagianId, string $soalId, int $urutan): void;

    public function perbaruiAtauBuatPivotBagianSoal(string $bagianId, string $bagianSoalId, int $urutan): void;

    /**
     * @return array<string>
     */
    public function idBagianSoalDalamBagianBerurutan(string $bagianId): array;

    /**
     * @return Collection<int, SoalBagianUjian>
     */
    public function barisSoalDenganSoal(string $bagianId): Collection;

    public function perbaruiUrutanPivotSoal(string $barisId, int $urutan): void;
}
