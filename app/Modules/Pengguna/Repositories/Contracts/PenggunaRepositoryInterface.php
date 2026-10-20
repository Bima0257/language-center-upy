<?php

namespace App\Modules\Pengguna\Repositories\Contracts;

use App\Models\Pengguna;
use App\Models\ProfilMahasiswa;
use Illuminate\Database\Eloquent\Collection;

interface PenggunaRepositoryInterface
{
    public function count(): int;

    public function penggunaMenungguVerifikasi(): Collection;

    public function temukanDenganEmail(string $email): ?Pengguna;

    public function create(array $data): Pengguna;

    public function buatProfilMahasiswa(Pengguna $pengguna, array $data): ProfilMahasiswa;

    public function perbaruiProfilMahasiswa(Pengguna $pengguna, array $data): ProfilMahasiswa;

    public function update(Pengguna $pengguna, array $data): Pengguna;
}
