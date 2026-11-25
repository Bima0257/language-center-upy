<?php

namespace App\Modules\Pengguna\Repositories;

use App\Models\Pengguna;
use App\Models\ProfilPeserta;
use App\Models\Role;
use App\Modules\Pengguna\Repositories\Contracts\PenggunaRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PenggunaRepository implements PenggunaRepositoryInterface
{
    private const SYSTEM_ROLE_NAMES = ['superadmin', 'admin', 'peserta'];

    private const ALLOWED_PER_PAGE = [20, 50, 100];

    private const ALLOWED_SORTS = ['name', 'email', 'created_at', 'aktif', 'sesi_ujian_count'];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateDaftar(array $filters): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;
        $role = $filters['role'] ?? 'admin';
        $aktif = $filters['aktif'] ?? null;
        $perPage = (int) ($filters['per_page'] ?? 20);
        $sort = $filters['sort'] ?? 'created_at';
        $dir = ($filters['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if (! in_array($perPage, self::ALLOWED_PER_PAGE, true)) {
            $perPage = 20;
        }

        if (! in_array($sort, self::ALLOWED_SORTS, true)) {
            $sort = 'created_at';
        }

        $query = Pengguna::query()
            ->with(['roles', 'profilPeserta.fakultas', 'profilPeserta.programStudi'])
            ->withCount('sesiUjian')
            ->whereDoesntHave('roles', fn ($roleQuery) => $roleQuery->where('name', 'superadmin'));

        if (filled($search)) {
            $query->where(function ($searchQuery) use ($search): void {
                $searchQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role === 'custom') {
            $query->whereHas('roles', fn ($roleQuery) => $roleQuery->whereNotIn('name', self::SYSTEM_ROLE_NAMES));
        } else {
            $query->role($role);
        }

        if ($aktif !== null && $aktif !== '') {
            $query->where('aktif', filter_var($aktif, FILTER_VALIDATE_BOOLEAN));
        }

        $query->orderBy($sort, $dir);

        if ($sort !== 'created_at') {
            $query->orderByDesc('created_at');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @return array<int, string>
     */
    public function daftarNamaRole(): array
    {
        return Role::query()
            ->where('name', '!=', 'superadmin')
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    public function count(): int
    {
        return Pengguna::count();
    }

    public function findOrFail(string $id): Pengguna
    {
        return Pengguna::with('profilPeserta')->findOrFail($id);
    }

    public function temukanUntukUpdate(string $id): Pengguna
    {
        return Pengguna::with('profilPeserta')->lockForUpdate()->findOrFail($id);
    }

    public function penggunaMenungguVerifikasi(): Collection
    {
        return Pengguna::role('peserta')
            ->whereHas('profilPeserta', fn ($q) => $q->whereNotNull('foto_identitas'))
            ->with(['profilPeserta.fakultas', 'profilPeserta.programStudi'])
            ->select(['id', 'name', 'email', 'foto', 'created_at'])
            ->get();
    }

    public function memilikiProfilDenganProgramStudi(string $programStudiId): bool
    {
        return ProfilPeserta::where('program_studi_id', $programStudiId)->exists();
    }

    public function temukanDenganEmail(string $email): ?Pengguna
    {
        return Pengguna::where('email', $email)->first();
    }

    public function create(array $data): Pengguna
    {
        return Pengguna::create($data);
    }

    public function buatProfilPeserta(Pengguna $pengguna, array $data): ProfilPeserta
    {
        return ProfilPeserta::create([...$data, 'pengguna_id' => $pengguna->id]);
    }

    public function perbaruiProfilPeserta(Pengguna $pengguna, array $data): ProfilPeserta
    {
        $profil = $pengguna->profilPeserta;

        if ($profil === null) {
            return $this->buatProfilPeserta($pengguna, $data);
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
