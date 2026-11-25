<?php

namespace App\Modules\PelaksanaanUjian\Repositories;

use App\Enums\AlasanSelesaiPart;
use App\Models\SesiPartUjian;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiPartUjianRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class SesiPartUjianRepository implements SesiPartUjianRepositoryInterface
{
    public function mulai(
        string $sesiUjianId,
        string $partUjianId,
        int $durasiMenit,
        Carbon $dimulaiPada,
    ): SesiPartUjian {
        return SesiPartUjian::firstOrCreate(
            [
                'sesi_ujian_id' => $sesiUjianId,
                'part_ujian_id' => $partUjianId,
            ],
            [
                'durasi_menit' => $durasiMenit,
                'dimulai_pada' => $dimulaiPada,
            ],
        );
    }

    public function temukanAktif(string $sesiUjianId): ?SesiPartUjian
    {
        return SesiPartUjian::where('sesi_ujian_id', $sesiUjianId)
            ->whereNull('selesai_pada')
            ->latest('dimulai_pada')
            ->first();
    }

    public function temukanAktifUntukUpdate(string $sesiUjianId): ?SesiPartUjian
    {
        return SesiPartUjian::where('sesi_ujian_id', $sesiUjianId)
            ->whereNull('selesai_pada')
            ->latest('dimulai_pada')
            ->lockForUpdate()
            ->first();
    }

    public function selesaikan(
        SesiPartUjian $timing,
        AlasanSelesaiPart $alasan,
        Carbon $selesaiPada,
    ): SesiPartUjian {
        $timing->update([
            'selesai_pada' => $selesaiPada,
            'alasan_selesai' => $alasan,
        ]);

        return $timing->refresh();
    }

    public function selesaikanSemuaTerbuka(
        string $sesiUjianId,
        AlasanSelesaiPart $alasan,
        Carbon $selesaiPada,
    ): int {
        return SesiPartUjian::where('sesi_ujian_id', $sesiUjianId)
            ->whereNull('selesai_pada')
            ->update([
                'selesai_pada' => $selesaiPada,
                'alasan_selesai' => $alasan,
            ]);
    }

    /**
     * @return Collection<int, SesiPartUjian>
     */
    public function riwayatUntukSesi(string $sesiUjianId): Collection
    {
        return SesiPartUjian::with('partUjian.section')
            ->where('sesi_ujian_id', $sesiUjianId)
            ->orderBy('dimulai_pada')
            ->get();
    }
}
