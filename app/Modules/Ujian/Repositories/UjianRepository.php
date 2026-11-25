<?php

namespace App\Modules\Ujian\Repositories;

use App\Enums\LingkupUjian;
use App\Enums\ModeUjian;
use App\Models\Ujian;
use App\Modules\Ujian\Repositories\Contracts\UjianRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class UjianRepository implements UjianRepositoryInterface
{
    public function semuaUntukDaftar(?string $programTesId = null): Collection
    {
        return Ujian::withCount('partUjian')
            ->withExists('periodeUjian')
            ->withExists('sesiUjian')
            ->with('programStudi:id,nama')
            ->when($programTesId !== null, fn ($query) => $query->where('program_tes_id', $programTesId))
            ->orderBy('judul')
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<string, int>
     */
    public function jumlahPerProgramTes(): \Illuminate\Support\Collection
    {
        return Ujian::query()
            ->select('program_tes_id')
            ->selectRaw('COUNT(id) AS jumlah_ujian')
            ->groupBy('program_tes_id')
            ->pluck('jumlah_ujian', 'program_tes_id');
    }

    public function temukanDenganRelasi(string $id): ?Ujian
    {
        return Ujian::with([
            'programStudi:id,nama',
            'partUjian' => fn ($query) => $query
                ->with(['bankSoal', 'section'])
                ->orderBy('urutan'),
        ])->find($id);
    }

    public function temukanDenganProgramTes(string $id): Ujian
    {
        return Ujian::with('programTes')->findOrFail($id);
    }

    public function temukanUntukUpdate(string $id): Ujian
    {
        return Ujian::with('programStudi:id,nama')
            ->lockForUpdate()
            ->findOrFail($id);
    }

    public function temukanTryout(string $id): ?Ujian
    {
        return Ujian::with(['programTes', 'programStudi:id,nama', 'partUjian.section'])
            ->where('mode', ModeUjian::TRYOUT)
            ->find($id);
    }

    public function create(array $data): Ujian
    {
        return Ujian::create($data);
    }

    public function update(Ujian $ujian, array $data): Ujian
    {
        $ujian->update($data);

        return $ujian->fresh();
    }

    public function delete(Ujian $ujian): void
    {
        $ujian->delete();
    }

    public function memilikiPeriodeUjian(Ujian $ujian): bool
    {
        return $ujian->periodeUjian()->exists();
    }

    public function memilikiSesi(Ujian $ujian): bool
    {
        return $ujian->sesiUjian()->exists();
    }

    public function memilikiProgramStudi(string $programStudiId): bool
    {
        return Ujian::query()
            ->whereHas('programStudi', fn (Builder $query) => $query->where('program_studi_id', $programStudiId))
            ->exists();
    }

    /**
     * @param  array<int, string>  $programStudiIds
     */
    public function sinkronkanProgramStudi(Ujian $ujian, array $programStudiIds): void
    {
        $ujian->programStudi()->sync($programStudiIds);
    }

    public function all(): Collection
    {
        return Ujian::with('partUjian')->get();
    }

    public function semuaAktifBerurutan(?string $programTesId = null): Collection
    {
        return Ujian::where('aktif', true)
            ->when($programTesId !== null, fn ($query) => $query->where('program_tes_id', $programTesId))
            ->orderBy('judul')
            ->get();
    }

    public function semuaAktifDenganProgramTes(): Collection
    {
        return Ujian::with('programTes')
            ->where('aktif', true)
            ->orderBy('judul')
            ->get();
    }

    public function semuaTryoutTersedia(?string $programStudiId = null): Collection
    {
        return Ujian::with('programTes')
            ->where('aktif', true)
            ->where('mode', ModeUjian::TRYOUT)
            ->whereHas('programTes', fn (Builder $query) => $query->where('aktif', true))
            ->where(function (Builder $query) use ($programStudiId): void {
                $query->where('lingkup', LingkupUjian::UMUM);

                if ($programStudiId !== null) {
                    $query->orWhere(fn (Builder $inner) => $inner
                        ->where('lingkup', LingkupUjian::PRODI)
                        ->whereHas(
                            'programStudi',
                            fn (Builder $pivot) => $pivot->where('program_studi_id', $programStudiId),
                        ));
                }
            })
            ->orderBy('judul')
            ->get();
    }

    public function semuaUntukDemo(): Collection
    {
        return Ujian::with('programTes')
            ->withCount('partUjian')
            ->withSum('partUjian as total_soal', 'jumlah_soal')
            ->orderBy('judul')
            ->get();
    }

    public function memilikiSoal(Ujian $ujian): bool
    {
        return $ujian->partUjian()
            ->whereHas('soalPartUjian')
            ->exists();
    }

    public function adaAktifUntukProgramTes(string $programTesId): bool
    {
        return Ujian::where('program_tes_id', $programTesId)
            ->where('aktif', true)
            ->exists();
    }

    public function count(): int
    {
        return Ujian::count();
    }
}
