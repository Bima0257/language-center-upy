<?php

namespace App\Modules\Ujian\Repositories;

use App\Models\Ujian;
use App\Modules\Ujian\Repositories\Contracts\UjianRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UjianRepository implements UjianRepositoryInterface
{
    public function semuaUntukDaftar(?string $programTesId = null): Collection
    {
        return Ujian::withCount('partUjian')
            ->withExists('periodeUjian')
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
            'partUjian' => fn ($query) => $query
                ->with(['bankSoal', 'section'])
                ->orderBy('urutan'),
        ])->find($id);
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

    public function count(): int
    {
        return Ujian::count();
    }
}
