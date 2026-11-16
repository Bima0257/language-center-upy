<?php

namespace App\Modules\Ujian\Repositories\Contracts;

use App\Models\PartSoal;
use App\Models\PartUjian;
use App\Models\SoalPartUjian;
use App\Models\Ujian;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

interface PartUjianRepositoryInterface
{
    public function create(array $data): PartUjian;

    /**
     * @return Collection<int, PartUjian>
     */
    public function semuaUntukUjian(Ujian $ujian): Collection;

    public function update(PartUjian $part, array $data): PartUjian;

    public function delete(PartUjian $part): void;

    public function soalDikelompokkanDenganPart(BaseCollection $partIds): BaseCollection;

    public function partSoalDikelompokkanDenganPart(BaseCollection $partIds): BaseCollection;

    /**
     * @param  array<string>  $partSoalIds
     * @return array<string>
     */
    public function idPartSoalDalamUjianAktif(array $partSoalIds): array;

    /**
     * @param  array<string>  $partSoalIds
     */
    public function hapusPartSoalDalamUjianNonaktif(array $partSoalIds): void;

    /**
     * @param  Collection<int, PartSoal>  $partSoal
     */
    public function sinkronkanPartSoalDalamUjianNonaktif(Collection $partSoal): void;

    /**
     * @return Collection<int, SoalPartUjian>
     */
    public function barisPivotDenganIdPart(BaseCollection $partIds): Collection;

    public function buatAtauTemukanPivotSoal(string $partId, string $soalId, int $urutan): void;

    public function buatPivotSoal(string $partId, string $soalId, int $urutan): void;

    /**
     * @return array<string>
     */
    public function idSoalDalamPart(string $partId): array;

    public function urutanSoalBerikutnya(string $partId): int;

    public function perbaruiUrutanSoal(string $partId, string $soalId, int $urutan): void;

    public function perbaruiAtauBuatPivotPartSoal(string $partId, string $partSoalId, int $urutan): void;

    /**
     * @return array<string>
     */
    public function idPartSoalDalamPartBerurutan(string $partId): array;

    /**
     * @return Collection<int, SoalPartUjian>
     */
    public function barisSoalDenganSoal(string $partId): Collection;

    public function perbaruiUrutanPivotSoal(string $barisId, int $urutan): void;

    public function jumlahSoalDalamPart(string $partId): int;

    public function perbaruiJumlahSoal(PartUjian $part, int $jumlah): void;
}
