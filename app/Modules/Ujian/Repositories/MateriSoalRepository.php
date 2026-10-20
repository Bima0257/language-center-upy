<?php

namespace App\Modules\Ujian\Repositories;

use App\Models\MateriSoal;
use App\Modules\Ujian\Repositories\Contracts\MateriSoalRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class MateriSoalRepository implements MateriSoalRepositoryInterface
{
    public function paginateDenganJumlah(int $perPage = 20): LengthAwarePaginator
    {
        return MateriSoal::withCount('soal')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function semuaBerurutan(): Collection
    {
        return MateriSoal::with('mediaMateriSoal')->orderBy('judul')->get();
    }

    public function find(string $id): ?MateriSoal
    {
        return MateriSoal::with('mediaMateriSoal')->find($id);
    }

    public function create(array $data): MateriSoal
    {
        return MateriSoal::create($data);
    }

    public function update(MateriSoal $materi, array $data): MateriSoal
    {
        $materi->update($data);

        return $materi->fresh();
    }

    public function delete(MateriSoal $materi): void
    {
        $materi->delete();
    }

    public function memilikiSoal(string $materiId): bool
    {
        return MateriSoal::whereKey($materiId)->whereHas('soal')->exists();
    }

    public function count(): int
    {
        return MateriSoal::count();
    }
}
