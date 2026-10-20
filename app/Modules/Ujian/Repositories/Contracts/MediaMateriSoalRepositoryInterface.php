<?php

namespace App\Modules\Ujian\Repositories\Contracts;

use App\Enums\JenisMedia;
use App\Models\MateriSoal;
use App\Models\MediaMateriSoal;

interface MediaMateriSoalRepositoryInterface
{
    public function create(array $data): MediaMateriSoal;

    public function gantiDenganJenis(MateriSoal $materi, JenisMedia $jenis, array $data): MediaMateriSoal;

    public function lokasiDigunakan(string $lokasiBerkas, string $kecualiMateriId): bool;
}
