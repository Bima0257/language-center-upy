<?php

namespace App\Modules\Ujian\Repositories\Contracts;

use App\Models\MateriSoal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface MateriSoalRepositoryInterface
{
    public function paginateDenganJumlah(int $perPage = 20): LengthAwarePaginator;

    public function semuaBerurutan(): Collection;

    public function find(string $id): ?MateriSoal;

    public function create(array $data): MateriSoal;

    public function update(MateriSoal $materi, array $data): MateriSoal;

    public function delete(MateriSoal $materi): void;

    public function memilikiSoal(string $materiId): bool;

    public function count(): int;
}
