<?php

namespace App\Modules\PelaksanaanUjian\Repositories;

use App\Models\Jawaban;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\JawabanRepositoryInterface;
use Illuminate\Support\Collection;

class JawabanRepository implements JawabanRepositoryInterface
{
    public function updateOrCreate(string $sesiId, string $soalId, array $data): Jawaban
    {
        return Jawaban::updateOrCreate(
            ['sesi_ujian_id' => $sesiId, 'soal_id' => $soalId],
            $data,
        );
    }

    public function ambilBerdasarkanSesi(string $sesiId): Collection
    {
        return Jawaban::where('sesi_ujian_id', $sesiId)->get();
    }

    public function getByQuestion(string $sesiId, string $soalId): ?Jawaban
    {
        return Jawaban::where('sesi_ujian_id', $sesiId)
            ->where('soal_id', $soalId)
            ->first();
    }
}
