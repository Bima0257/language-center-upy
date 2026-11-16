<?php

namespace App\Modules\Penilaian\Repositories\Contracts;

use App\Models\AturanPenilaian;
use Illuminate\Database\Eloquent\Collection;

interface AturanPenilaianRepositoryInterface
{
    /**
     * @return Collection<int, AturanPenilaian>
     */
    public function semuaUntukSkema(string $skemaPenilaianId): Collection;

    public function findForSection(string $skemaPenilaianId, string $sectionId): ?AturanPenilaian;

    public function updateOrCreate(
        string $skemaPenilaianId,
        string $sectionId,
        array $data,
    ): AturanPenilaian;

    public function salinKeSkema(string $skemaAsalId, string $skemaTujuanId): void;
}
