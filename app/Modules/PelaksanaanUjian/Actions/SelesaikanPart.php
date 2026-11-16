<?php

namespace App\Modules\PelaksanaanUjian\Actions;

use App\Models\PartUjian;
use App\Models\SesiUjian;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiUjianRepositoryInterface;

class SelesaikanPart
{
    public function __construct(
        private SesiUjianRepositoryInterface $sesiRepo,
    ) {}

    public function execute(SesiUjian $sesi): void
    {
        $partUjianAktif = $sesi->partUjianAktif;
        if (! $partUjianAktif) {
            return;
        }

        $partBerikutnya = PartUjian::query()
            ->where('ujian_id', $partUjianAktif->ujian_id)
            ->where('urutan', '>', $partUjianAktif->urutan)
            ->orderBy('urutan')
            ->first();

        $this->sesiRepo->update($sesi->id, [
            'part_ujian_aktif_id' => $partBerikutnya?->id,
        ]);
    }
}
