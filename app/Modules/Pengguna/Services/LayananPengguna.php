<?php

namespace App\Modules\Pengguna\Services;

use App\Modules\Pengguna\Repositories\Contracts\PenggunaRepositoryInterface;

class LayananPengguna
{
    private const TAB_ROLE_NAMES = ['admin', 'peserta'];

    public function __construct(
        private PenggunaRepositoryInterface $penggunaRepo,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dataDaftar(array $filters): array
    {
        $selectedRole = $filters['f_role'] ?? $filters['role'] ?? self::TAB_ROLE_NAMES[0];
        $search = $filters['q'] ?? $filters['search'] ?? null;
        $aktif = $filters['f_aktif'] ?? $filters['aktif'] ?? null;
        $perPage = (int) ($filters['per_page'] ?? 20);
        $sort = $filters['sort'] ?? 'created_at';
        $dir = $filters['dir'] ?? 'desc';

        $users = $this->penggunaRepo->paginateDaftar([
            'search' => $search,
            'role' => $selectedRole,
            'aktif' => $aktif,
            'per_page' => $perPage,
            'sort' => $sort,
            'dir' => $dir,
        ]);

        return [
            'users' => $users,
            'roles' => $this->penggunaRepo->daftarNamaRole(),
            'systemRoles' => self::TAB_ROLE_NAMES,
            'filters' => [
                'search' => $search,
                'aktif' => $aktif,
                'role' => $selectedRole,
            ],
        ];
    }
}
