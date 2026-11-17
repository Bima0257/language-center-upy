<?php

namespace App\Modules\Penilaian\Repositories;

use App\Models\AturanPenilaian;
use App\Modules\Penilaian\Repositories\Contracts\AturanPenilaianRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AturanPenilaianRepository implements AturanPenilaianRepositoryInterface
{
    /**
     * @return Collection<int, AturanPenilaian>
     */
    public function semuaUntukSkema(string $skemaPenilaianId): Collection
    {
        return AturanPenilaian::with(['section', 'skemaPenilaian'])
            ->where('skema_penilaian_id', $skemaPenilaianId)
            ->orderBy('kode_section')
            ->get();
    }

    public function findForSection(string $skemaPenilaianId, string $sectionId): ?AturanPenilaian
    {
        return AturanPenilaian::where('skema_penilaian_id', $skemaPenilaianId)
            ->where('section_id', $sectionId)
            ->first();
    }

    public function updateOrCreate(
        string $skemaPenilaianId,
        string $sectionId,
        array $data,
    ): AturanPenilaian {
        return AturanPenilaian::updateOrCreate(
            [
                'skema_penilaian_id' => $skemaPenilaianId,
                'section_id' => $sectionId,
            ],
            $data + [
                'skema_penilaian_id' => $skemaPenilaianId,
                'section_id' => $sectionId,
            ],
        );
    }
}
