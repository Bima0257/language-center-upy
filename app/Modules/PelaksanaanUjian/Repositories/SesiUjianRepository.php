<?php

namespace App\Modules\PelaksanaanUjian\Repositories;

use App\Enums\ModeUjian;
use App\Enums\StatusSesi;
use App\Models\SesiUjian;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiUjianRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SesiUjianRepository implements SesiUjianRepositoryInterface
{
    public function findOrFail(string $id): SesiUjian
    {
        return SesiUjian::with([
            'catatanPelanggaran',
            'ujian.programTes',
            'ujian.partUjian.section',
            'slotUjian.periodeUjian.ujian',
            'slotUjian',
            'partUjianAktif.section',
            'skemaPenilaian',
            'hasilPenilaian.section',
        ])->findOrFail($id);
    }

    public function temukanUntukUpdate(string $id): SesiUjian
    {
        return SesiUjian::with([
            'ujian.programTes',
            'ujian.partUjian.section',
            'slotUjian.periodeUjian.ujian',
            'slotUjian',
            'partUjianAktif.section',
        ])->lockForUpdate()->findOrFail($id);
    }

    public function temukanDenganJawaban(string $id): ?SesiUjian
    {
        return SesiUjian::with([
            'jawaban',
            'ujian.programTes',
            'ujian.partUjian',
            'slotUjian.periodeUjian.ujian.partUjian',
        ])->find($id);
    }

    public function temukanDenganJawabanUntukUpdate(string $id): ?SesiUjian
    {
        return SesiUjian::with([
            'jawaban',
            'ujian.programTes',
            'ujian.partUjian',
            'slotUjian.periodeUjian.ujian.partUjian',
        ])->lockForUpdate()->find($id);
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
        return SesiUjian::with('pengguna.profilPeserta')
            ->whereHas('slotUjian', fn ($q) => $q->where('periode_ujian_id', $periodeUjianId))
            ->where('status', StatusSesi::IN_PROGRESS)
            ->get();
    }

    public function ambilSesiDitandai(bool $sertakanDitinjau = false): Collection
    {
        $query = SesiUjian::with([
            'pengguna.profilPeserta',
            'catatanPelanggaran',
            'ujian.programTes',
            'slotUjian.periodeUjian.ujian',
            'slotUjian',
        ])->where('ditandai', true);

        if (! $sertakanDitinjau) {
            $query->whereNull('ditinjau_pada');
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function ambilSesiAktif(): Collection
    {
        return SesiUjian::with([
            'pengguna.profilPeserta',
            'ujian.programTes',
            'slotUjian.periodeUjian.ujian',
            'slotUjian',
        ])
            ->withCount('jawaban')
            ->where('status', StatusSesi::IN_PROGRESS)
            ->get();
    }

    public function terbaruUntukPengguna(string $penggunaId, int $batas = 3): Collection
    {
        return SesiUjian::with('ujian', 'slotUjian.periodeUjian.ujian', 'slotUjian')
            ->where('pengguna_id', $penggunaId)
            ->orderBy('created_at', 'desc')
            ->take($batas)
            ->get();
    }

    public function paginateRiwayatUntukPengguna(
        string $penggunaId,
        ModeUjian $mode,
        ?string $pencarian,
        int $perHalaman = 10,
    ): LengthAwarePaginator {
        return SesiUjian::query()
            ->with([
                'ujian.programTes',
                'slotUjian.periodeUjian.ujian.programTes',
            ])
            ->where('pengguna_id', $penggunaId)
            ->whereIn('status', [StatusSesi::SUBMITTED, StatusSesi::TERMINATED])
            ->when(
                $mode === ModeUjian::OFFICIAL,
                fn (Builder $query) => $query->whereNotNull('slot_ujian_id'),
            )
            ->when(
                $mode === ModeUjian::TRYOUT,
                fn (Builder $query) => $query
                    ->whereNull('slot_ujian_id')
                    ->whereNotNull('ujian_id'),
            )
            ->when(filled($pencarian), function (Builder $query) use ($pencarian): void {
                $query->where(function (Builder $query) use ($pencarian): void {
                    $query->whereHas('ujian', fn (Builder $ujianQuery) => $ujianQuery
                        ->where('judul', 'like', "%{$pencarian}%")
                        ->orWhereHas('programTes', fn (Builder $programQuery) => $programQuery
                            ->where('nama', 'like', "%{$pencarian}%")))
                        ->orWhereHas('slotUjian.periodeUjian.ujian', fn (Builder $ujianQuery) => $ujianQuery
                            ->where('judul', 'like', "%{$pencarian}%")
                            ->orWhereHas('programTes', fn (Builder $programQuery) => $programQuery
                                ->where('nama', 'like', "%{$pencarian}%")));
                });
            })
            ->orderByDesc('created_at')
            ->paginate($perHalaman)
            ->withQueryString();
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
