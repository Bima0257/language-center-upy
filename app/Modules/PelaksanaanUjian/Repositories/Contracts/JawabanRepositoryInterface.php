<?php

namespace App\Modules\PelaksanaanUjian\Repositories\Contracts;

use App\Models\Jawaban;
use Illuminate\Support\Collection;

interface JawabanRepositoryInterface
{
    public function updateOrCreate(string $sesiId, string $soalId, array $data): Jawaban;

    public function ambilBerdasarkanSesi(string $sesiId): Collection;

    public function getByQuestion(string $sesiId, string $soalId): ?Jawaban;
}
