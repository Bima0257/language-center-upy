<?php

namespace App\Modules\PelaksanaanUjian\Repositories;

use App\Enums\StatusSesi;
use App\Models\SesiUjian;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiUjianRepositoryInterface;
use Illuminate\Support\Collection;

class SesiUjianRepository implements SesiUjianRepositoryInterface
{
    public function findOrFail(string $id): SesiUjian
    {
        return SesiUjian::with(['catatanPelanggaran', 'slotUjian.periodeUjian.ujian', 'slotUjian', 'bagianAktif'])->findOrFail($id);
    }

    public function temukanDenganJawaban(string $id): ?SesiUjian
    {
        return SesiUjian::with(['jawaban', 'slotUjian.periodeUjian.ujian.bagianUjian'])->find($id);
    }

    public function create(array $data): SesiUjian
    {
        return SesiUjian::create($data);
    }

    public function update(string $id, array $data): SesiUjian
    {
        $sesi = SesiUjian::findOrFail($id);
        $sesi->update($data);

        return $sesi->fresh();
    }

    public function memilikiSesiAktif(string $penggunaId): bool
    {
        return SesiUjian::where('pengguna_id', $penggunaId)
            ->whereIn('status', [StatusSesi::PENDING, StatusSesi::IN_PROGRESS])
            ->exists();
    }

    public function temukanSesiAktif(string $penggunaId): ?SesiUjian
    {
        return SesiUjian::where('pengguna_id', $penggunaId)
            ->whereIn('status', [StatusSesi::PENDING, StatusSesi::IN_PROGRESS])
            ->latest('updated_at')
            ->first();
    }

    public function memilikiSesiUntukPeriode(string $penggunaId, string $periodeUjianId): bool
    {
        return SesiUjian::where('pengguna_id', $penggunaId)
            ->whereHas('slotUjian', fn ($q) => $q->where('periode_ujian_id', $periodeUjianId))
            ->exists();
    }

    public function ambilSesiAktifDenganPeriode(string $periodeUjianId): Collection
    {
        return SesiUjian::with('pengguna')
            ->whereHas('slotUjian', fn ($q) => $q->where('periode_ujian_id', $periodeUjianId))
            ->where('status', StatusSesi::IN_PROGRESS)
            ->get();
    }

    public function ambilSesiDitandai(bool $sertakanDitinjau = false): Collection
    {
        $query = SesiUjian::with(['pengguna', 'catatanPelanggaran', 'slotUjian.periodeUjian.ujian', 'slotUjian'])
            ->where('ditandai', true);

        if (! $sertakanDitinjau) {
            $query->whereNull('ditinjau_pada');
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function ambilSesiAktif(): Collection
    {
        return SesiUjian::with(['pengguna', 'slotUjian.periodeUjian.ujian', 'slotUjian'])
            ->withCount('jawaban')
            ->where('status', StatusSesi::IN_PROGRESS)
            ->get();
    }

    public function terbaruUntukPengguna(string $penggunaId, int $batas = 3): Collection
    {
        return SesiUjian::with('slotUjian.periodeUjian.ujian', 'slotUjian')
            ->where('pengguna_id', $penggunaId)
            ->orderBy('created_at', 'desc')
            ->take($batas)
            ->get();
    }

    public function hitungAktif(): int
    {
        return SesiUjian::where('status', StatusSesi::IN_PROGRESS)->count();
    }

    public function hitungDitandaiMenungguTinjauan(): int
    {
        return SesiUjian::where('ditandai', true)->whereNull('ditinjau_pada')->count();
    }
}
