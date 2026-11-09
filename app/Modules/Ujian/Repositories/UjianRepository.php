<?php

namespace App\Modules\Ujian\Repositories;

use App\Models\Ujian;
use App\Modules\Ujian\Repositories\Contracts\UjianRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UjianRepository implements UjianRepositoryInterface
{
    public function semuaUntukDaftar(?string $jenisTesId = null): Collection
    {
        return Ujian::withCount('bagianUjian')
            ->withExists('periodeUjian')
            ->when($jenisTesId !== null, fn ($query) => $query->where('jenis_tes_id', $jenisTesId))
            ->orderBy('judul')
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<string, int>
     */
    public function jumlahPerJenisTes(): \Illuminate\Support\Collection
    {
        return Ujian::query()
            ->select('jenis_tes_id')
            ->selectRaw('COUNT(id) AS jumlah_ujian')
            ->groupBy('jenis_tes_id')
            ->pluck('jumlah_ujian', 'jenis_tes_id');
    }

    public function temukanDenganRelasi(string $id): ?Ujian
    {
        return Ujian::with([
            'bagianUjian' => fn ($query) => $query
                ->with(['bankSoal', 'keterampilan'])
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
        return Ujian::with('bagianUjian')->get();
    }

    public function semuaAktifBerurutan(?string $jenisTesId = null): Collection
    {
        return Ujian::where('aktif', true)
            ->when($jenisTesId !== null, fn ($query) => $query->where('jenis_tes_id', $jenisTesId))
            ->orderBy('judul')
            ->get();
    }

    public function semuaAktifDenganJenisTes(): Collection
    {
        return Ujian::with('jenisTes')
            ->where('aktif', true)
            ->orderBy('judul')
            ->get();
    }

    public function count(): int
    {
        return Ujian::count();
    }
}
