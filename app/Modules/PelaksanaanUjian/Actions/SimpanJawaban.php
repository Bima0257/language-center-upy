<?php

namespace App\Modules\PelaksanaanUjian\Actions;

use App\Modules\PelaksanaanUjian\Repositories\Contracts\JawabanRepositoryInterface;

class SimpanJawaban
{
    public function __construct(
        private JawabanRepositoryInterface $jawabanRepo,
    ) {}

    public function execute(string $sesiId, string $soalId, ?string $jawaban): void
    {
        $this->jawabanRepo->updateOrCreate($sesiId, $soalId, [
            'teks_jawaban' => $jawaban,
        ]);
    }
}
