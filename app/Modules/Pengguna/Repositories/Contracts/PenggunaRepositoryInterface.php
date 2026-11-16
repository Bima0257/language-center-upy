<?php

namespace App\Modules\Pengguna\Repositories\Contracts;

use App\Models\Pengguna;
use App\Models\ProfilMahasiswa;
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

    public function buatProfilMahasiswa(Pengguna $pengguna, array $data): ProfilMahasiswa;

    public function perbaruiProfilMahasiswa(Pengguna $pengguna, array $data): ProfilMahasiswa;

    public function update(Pengguna $pengguna, array $data): Pengguna;
}
