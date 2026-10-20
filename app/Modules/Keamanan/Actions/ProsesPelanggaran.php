<?php

namespace App\Modules\Keamanan\Actions;

use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiUjianRepositoryInterface;

class ProsesPelanggaran
{
    public function __construct(
        private SesiUjianRepositoryInterface $sesiRepo,
    ) {}

    public function execute(string $sesiId): array
    {
        $sesi = $this->sesiRepo->findOrFail($sesiId);
        $pelanggaran = $sesi->jumlah_pelanggaran;
        $batasPelanggaran = $sesi->batasPelanggaranMaksimum();

        return [
            'pelanggaran' => $pelanggaran,
            'dihentikan' => $pelanggaran >= $batasPelanggaran,
            'peringatanTersisa' => max(0, $batasPelanggaran - $pelanggaran),
            'batasPelanggaran' => $batasPelanggaran,
        ];
    }
}
