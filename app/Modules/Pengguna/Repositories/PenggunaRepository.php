<?php

namespace App\Modules\Pengguna\Repositories;

use App\Models\Pengguna;
use App\Models\ProfilMahasiswa;
use App\Modules\Pengguna\Repositories\Contracts\PenggunaRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PenggunaRepository implements PenggunaRepositoryInterface
{
    public function count(): int
    {
        return Pengguna::count();
    }

    public function penggunaMenungguVerifikasi(): Collection
    {
        return Pengguna::role('student')
            ->whereHas('profilMahasiswa', fn ($q) => $q->whereNotNull('foto_identitas'))
            ->with(['profilMahasiswa.fakultas', 'profilMahasiswa.programStudi'])
            ->select(['id', 'name', 'email', 'foto', 'created_at'])
            ->get();
    }

    public function temukanDenganEmail(string $email): ?Pengguna
    {
        return Pengguna::where('email', $email)->first();
    }

    public function create(array $data): Pengguna
    {
        return Pengguna::create($data);
    }

    public function buatProfilMahasiswa(Pengguna $pengguna, array $data): ProfilMahasiswa
    {
        return ProfilMahasiswa::create([...$data, 'pengguna_id' => $pengguna->id]);
    }

    public function perbaruiProfilMahasiswa(Pengguna $pengguna, array $data): ProfilMahasiswa
    {
        $profil = $pengguna->profilMahasiswa;

        if ($profil === null) {
            return $this->buatProfilMahasiswa($pengguna, $data);
        }

        $profil->update($data);

        return $profil->fresh();
    }

    public function update(Pengguna $pengguna, array $data): Pengguna
    {
        $pengguna->update($data);

        return $pengguna->fresh();
    }
}
