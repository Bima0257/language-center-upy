<?php

namespace App\Modules\PelaksanaanUjian\Actions;

use App\Models\BagianUjian;
use App\Models\SesiUjian;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiUjianRepositoryInterface;

class SelesaikanBagian
{
    public function __construct(
        private SesiUjianRepositoryInterface $sesiRepo,
    ) {}

    public function execute(SesiUjian $sesi): void
    {
        $bagianAktif = $sesi->bagianAktif;
        if (! $bagianAktif) {
            return;
        }

        $bagianBerikutnya = BagianUjian::query()
            ->where('ujian_id', $bagianAktif->ujian_id)
            ->where('urutan', '>', $bagianAktif->urutan)
            ->orderBy('urutan')
            ->first();

        $this->sesiRepo->update($sesi->id, [
            'bagian_aktif_id' => $bagianBerikutnya?->id,
        ]);
    }
}
