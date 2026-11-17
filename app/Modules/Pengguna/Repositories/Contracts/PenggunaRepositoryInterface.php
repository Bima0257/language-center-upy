<?php

namespace App\Modules\Pengguna\Repositories\Contracts;

use App\Models\Pengguna;
use App\Models\ProfilPeserta;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PenggunaRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateDaftar(array $filters): LengthAwarePaginator;

    /**
     * @return array<int, string>
     */
    public function daftarNamaRole(): array;

    public function count(): int;

    public function findOrFail(string $id): Pengguna;

    public function penggunaMenungguVerifikasi(): Collection;

    public function temukanDenganEmail(string $email): ?Pengguna;

    public function create(array $data): Pengguna;

    public function buatProfilPeserta(Pengguna $pengguna, array $data): ProfilPeserta;

    public function perbaruiProfilPeserta(Pengguna $pengguna, array $data): ProfilPeserta;

    public function update(Pengguna $pengguna, array $data): Pengguna;
}
