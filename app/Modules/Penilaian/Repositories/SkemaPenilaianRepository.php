<?php

namespace App\Modules\Penilaian\Repositories;

use App\Enums\StatusSkemaPenilaian;
use App\Models\SkemaPenilaian;
use App\Modules\Penilaian\Repositories\Contracts\SkemaPenilaianRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SkemaPenilaianRepository implements SkemaPenilaianRepositoryInterface
{
    /**
     * @return Collection<int, SkemaPenilaian>
     */
    public function semuaDenganProgramTes(?string $programTesId = null): Collection
    {
        return SkemaPenilaian::with('programTes')
            ->withCount(['aturanPenilaian', 'interpretasiSkor'])
            ->when($programTesId !== null, fn ($query) => $query->where('program_tes_id', $programTesId))
            ->orderBy('program_tes_id')
            ->orderByDesc('versi')
            ->get();
    }

    public function temukanDenganRelasi(string $id): ?SkemaPenilaian
    {
        return SkemaPenilaian::with([
            'programTes',
            'aturanPenilaian.section',
            'interpretasiSkor',
        ])->find($id);
    }

    public function temukanAktif(string $programTesId): ?SkemaPenilaian
    {
        return SkemaPenilaian::with([
            'programTes',
            'aturanPenilaian.section',
            'interpretasiSkor',
        ])
            ->where('program_tes_id', $programTesId)
            ->where('status', StatusSkemaPenilaian::AKTIF)
            ->first();
    }

    public function temukanAktifUntukUpdate(string $programTesId): ?SkemaPenilaian
    {
        return SkemaPenilaian::with(['aturanPenilaian', 'interpretasiSkor'])
            ->where('program_tes_id', $programTesId)
            ->where('status', StatusSkemaPenilaian::AKTIF)
            ->lockForUpdate()
            ->first();
    }

    public function versiTerakhir(string $programTesId): ?SkemaPenilaian
    {
        return SkemaPenilaian::with([
            'programTes',
            'aturanPenilaian.section',
            'interpretasiSkor',
        ])
            ->where('program_tes_id', $programTesId)
            ->orderByDesc('versi')
            ->first();
    }

    public function buat(array $data): SkemaPenilaian
    {
        return SkemaPenilaian::create($data);
    }

    public function perbarui(SkemaPenilaian $skema, array $data): SkemaPenilaian
    {
        $skema->update($data);

        return $skema->fresh();
    }

    public function arsipkanSelain(string $programTesId, string $kecualiId): void
    {
        SkemaPenilaian::where('program_tes_id', $programTesId)
            ->where('id', '!=', $kecualiId)
            ->where('status', StatusSkemaPenilaian::AKTIF)
            ->update(['status' => StatusSkemaPenilaian::ARSIP]);
    }
}
