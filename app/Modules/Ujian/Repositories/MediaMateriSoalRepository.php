<?php

namespace App\Modules\Ujian\Repositories;

use App\Enums\JenisMedia;
use App\Models\MateriSoal;
use App\Models\MediaMateriSoal;
use App\Modules\Ujian\Repositories\Contracts\MediaMateriSoalRepositoryInterface;

class MediaMateriSoalRepository implements MediaMateriSoalRepositoryInterface
{
    public function create(array $data): MediaMateriSoal
    {
        return MediaMateriSoal::create($data);
    }

    public function gantiDenganJenis(MateriSoal $materi, JenisMedia $jenis, array $data): MediaMateriSoal
    {
        $materi->mediaMateriSoal()->where('jenis_media', $jenis->value)->delete();

        return MediaMateriSoal::create($data + ['jenis_media' => $jenis, 'materi_soal_id' => $materi->id]);
    }

    public function lokasiDigunakan(string $lokasiBerkas, string $kecualiMateriId): bool
    {
        return MediaMateriSoal::where('lokasi_berkas', $lokasiBerkas)
            ->where('materi_soal_id', '!=', $kecualiMateriId)
            ->exists();
    }
}
