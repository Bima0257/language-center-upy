<?php

namespace App\Modules\Ujian\Repositories\Contracts;

use App\Models\KebijakanMateriSoal;
use Illuminate\Database\Eloquent\Collection;

interface KebijakanMateriSoalRepositoryInterface
{
    public function temukanDenganKeterampilan(string $keterampilanId): ?KebijakanMateriSoal;

    public function create(array $data): KebijakanMateriSoal;

    public function update(KebijakanMateriSoal $kebijakan, array $data): KebijakanMateriSoal;

    public function delete(KebijakanMateriSoal $kebijakan): void;

    /**
     * @param  array<string, string>  $aturanMedia
     */
    public function sinkronkanAturanMedia(KebijakanMateriSoal $kebijakan, array $aturanMedia): void;

    /**
     * @return Collection<int, KebijakanMateriSoal>
     */
    public function all(): Collection;
}
