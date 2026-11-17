<?php

namespace App\Modules\Identity\Services;

use App\Enums\JenisPeserta;
use App\Models\Pengguna;
use App\Models\Permission;
use App\Models\PermissionCategory;
use App\Models\ProgramStudi;
use App\Models\Role;
use App\Modules\Identity\Contracts\IdentityContract;
use App\Modules\Pengguna\Repositories\Contracts\PenggunaRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class IdentityService implements IdentityContract
{
    private const PER_PAGE = [10, 25, 50, 100];

    private const USER_SORTS = ['name', 'username', 'email', 'status', 'last_login'];

    private const ROLE_SORTS = ['name', 'users_count', 'is_system', 'is_default'];

    private const CATEGORY_SORTS = ['module', 'name', 'code', 'permissions_count', 'is_system'];

    private const DEFAULT_ROLES = ['admin', 'peserta'];

    /** @var array<string, array<int, string>> */
    private array $roleBranchCache = [];

    public function __construct(
        private PermissionScanner $permissionScanner,
        private PenggunaRepositoryInterface $penggunaRepo,
    ) {}

    public function listUsersPaginated(array $params): LengthAwarePaginator
    {
        return $this->buildUsersQuery($params)
            ->paginate($this->perPage($params))
            ->withQueryString()
            ->through(fn (Pengguna $pengguna): array => $this->mapUser($pengguna));
    }

    public function countUsersByTab(array $params): array
    {
        return [
            'admin' => $this->buildUsersQuery($params, 'admin')->count(),
            'peserta' => $this->buildUsersQuery($params, 'peserta')->count(),
            'custom' => $this->buildUsersQuery($params, 'custom')->count(),
        ];
    }

    public function findUserPage(string $userId, array $params): ?int
    {
        $query = $this->buildUsersQuery($params)
            ->select('users.id')
            ->selectRaw(sprintf(
                'ROW_NUMBER() OVER (ORDER BY %s %s, users.name asc, users.id asc) as row_number',
                $this->userSortColumn($params),
                $this->direction($params),
            ));

        $rowNumber = DB::query()
            ->fromSub($query, 'ranked_users')
            ->where('id', $userId)
            ->value('row_number');

        if ($rowNumber === null) {
            return null;
        }

        return (int) ceil((int) $rowNumber / $this->perPage($params));
    }

    public function listRoleOptions(): array
    {
        return Role::query()
            ->where('name', '!=', 'superadmin')
            ->orderBy('name')
            ->get(['id', 'name', 'is_default', 'is_system'])
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'is_default' => $role->is_default,
                'is_system' => $role->is_system,
                'parent_ids' => $role->parents()->pluck('roles.id')->all(),
            ])
            ->all();
    }

    public function listProgramStudiOptions(): array
    {
        return ProgramStudi::query()
            ->where('aktif', true)
            ->orderBy('nama')
            ->get(['id', 'nama'])
            ->map(fn (ProgramStudi $programStudi): array => [
                'value' => $programStudi->id,
                'label' => $programStudi->nama,
            ])
            ->all();
    }

    public function createUser(array $data): Pengguna
    {
        $roles = $this->rolesFromIds($data['role_ids'] ?? []);
        $this->guardRoleSelection($roles);

        $status = (bool) ($data['status'] ?? true);
        $jenisPeserta = JenisPeserta::tryFrom((string) ($data['jenis_peserta'] ?? '')) ?? JenisPeserta::MAHASISWA;
        $memerlukanAkademik = in_array($jenisPeserta, [JenisPeserta::MAHASISWA, JenisPeserta::DOSEN], true);
        $programStudiId = $data['program_studi_id'] ?? null;
        $memilikiRolePeserta = $roles->contains('name', 'peserta');

        if ($memilikiRolePeserta && $memerlukanAkademik && blank($programStudiId)) {
            throw new \RuntimeException('Program studi wajib dipilih untuk peserta mahasiswa atau dosen.');
        }

        if ($memilikiRolePeserta && $jenisPeserta === JenisPeserta::UMUM && blank($data['institusi'] ?? null)) {
            throw new \RuntimeException('Institusi wajib diisi untuk peserta umum.');
        }

        $programStudi = $programStudiId !== null
            ? ProgramStudi::query()->find($programStudiId)
            : null;

        return DB::transaction(function () use ($data, $roles, $status, $jenisPeserta, $programStudi, $memilikiRolePeserta): Pengguna {
            $pengguna = Pengguna::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'status' => $status,
                'aktif' => $status,
                'email_verified_at' => now(),
            ]);

            $pengguna->syncRoles($roles->pluck('name')->all());
            $this->syncUserProgramStudi($pengguna, $programStudi?->id);

            if ($memilikiRolePeserta) {
                $this->penggunaRepo->buatProfilPeserta($pengguna, [
                    'jenis_peserta' => $jenisPeserta->value,
                    'nomor_identitas' => $data['nomor_identitas'] ?? null,
                    'institusi' => $data['institusi'] ?? null,
                    'fakultas_id' => $programStudi?->fakultas_id,
                    'program_studi_id' => $programStudi?->id,
                    'tahun_mulai' => $data['tahun_mulai'] ?? null,
                ]);
            }

            return $pengguna->fresh(['roles', 'programStudis', 'profilPeserta']);
        });
    }

    public function createUserBulk(array $users): int
    {
        return DB::transaction(function () use ($users): int {
            foreach ($users as $index => $user) {
                try {
                    $this->createUser($user);
                } catch (\Throwable $exception) {
                    throw new \RuntimeException('Baris '.($index + 1).': '.$exception->getMessage());
                }
            }

            return count($users);
        });
    }

    public function updateUser(Pengguna $pengguna, array $data): Pengguna
    {
        if ($pengguna->hasRole('superadmin')) {
            throw new \RuntimeException('Akun superadmin tidak dapat diubah dari pengelolaan pengguna.');
        }

        $roles = array_key_exists('role_ids', $data)
            ? $this->rolesFromIds($data['role_ids'])
            : null;

        if ($roles !== null) {
            $this->guardRoleSelection($roles);
        }

        return DB::transaction(function () use ($pengguna, $data, $roles): Pengguna {
            $status = array_key_exists('status', $data) ? (bool) $data['status'] : $pengguna->status;

            $payload = [
                'name' => $data['name'] ?? $pengguna->name,
                'username' => $data['username'] ?? $pengguna->username,
                'email' => $data['email'] ?? $pengguna->email,
                'status' => $status,
                'aktif' => $status,
            ];

            if (filled($data['password'] ?? null)) {
                $payload['password'] = $data['password'];
            }

            $pengguna->update($payload);

            if ($roles !== null) {
                $pengguna->syncRoles($roles->pluck('name')->all());
            }

            $memilikiRolePeserta = $roles?->contains('name', 'peserta') ?? $pengguna->hasRole('peserta');
            $profil = $pengguna->loadMissing('profilPeserta')->profilPeserta;
            $jenisDariRequest = JenisPeserta::tryFrom((string) ($data['jenis_peserta'] ?? ''));
            $jenisPeserta = $jenisDariRequest
                ?? ($profil !== null ? $profil->jenis_peserta : JenisPeserta::MAHASISWA);
            $memerlukanAkademik = in_array($jenisPeserta, [JenisPeserta::MAHASISWA, JenisPeserta::DOSEN], true);
            $programStudiId = array_key_exists('program_studi_id', $data)
                ? $data['program_studi_id']
                : $profil?->program_studi_id;
            $programStudi = $programStudiId !== null
                ? ProgramStudi::query()->find($programStudiId)
                : null;

            if ($memilikiRolePeserta) {
                if ($memerlukanAkademik && blank($programStudiId)) {
                    throw new \RuntimeException('Program studi wajib dipilih untuk peserta mahasiswa atau dosen.');
                }

                $institusi = array_key_exists('institusi', $data)
                    ? $data['institusi']
                    : $profil?->institusi;

                if ($jenisPeserta === JenisPeserta::UMUM && blank($institusi)) {
                    throw new \RuntimeException('Institusi wajib diisi untuk peserta umum.');
                }

                $dataProfil = [
                    'jenis_peserta' => $jenisPeserta->value,
                    'nomor_identitas' => array_key_exists('nomor_identitas', $data)
                        ? $data['nomor_identitas']
                        : $profil?->nomor_identitas,
                    'institusi' => $institusi,
                    'fakultas_id' => $memerlukanAkademik ? $programStudi?->fakultas_id : null,
                    'program_studi_id' => $memerlukanAkademik ? $programStudi?->id : null,
                    'tahun_mulai' => array_key_exists('tahun_mulai', $data)
                        ? $data['tahun_mulai']
                        : $profil?->tahun_mulai,
                ];

                if ($profil === null) {
                    $this->penggunaRepo->buatProfilPeserta($pengguna, $dataProfil);
                } else {
                    $this->penggunaRepo->perbaruiProfilPeserta($pengguna, $dataProfil);
                }

                $this->syncUserProgramStudi($pengguna, $memerlukanAkademik ? $programStudi?->id : null);
            } elseif (array_key_exists('program_studi_id', $data)) {
                $this->syncUserProgramStudi($pengguna, $data['program_studi_id']);
            }

            return $pengguna->fresh(['roles', 'programStudis', 'profilPeserta']);
        });
    }

    public function deleteUser(Pengguna $pengguna): void
    {
        if (auth()->id() === $pengguna->id) {
            throw new \RuntimeException('Anda tidak dapat menghapus akun sendiri.');
        }

        if ($pengguna->hasRole('superadmin')) {
            throw new \RuntimeException('Akun superadmin tidak dapat dihapus.');
        }

        DB::transaction(function () use ($pengguna): void {
            $pengguna->roles()->detach();
            $pengguna->programStudis()->detach();
            $pengguna->delete();
        });
    }

    public function listRolesPaginated(array $params): LengthAwarePaginator
    {
        return $this->buildRolesQuery($params)
            ->paginate($this->perPage($params))
            ->withQueryString()
            ->through(fn (Role $role): array => $this->mapRole($role));
    }

    public function countRolesByTab(array $params): array
    {
        return [
            'default' => $this->buildRolesQuery($params, 'default')->count(),
            'system' => $this->buildRolesQuery($params, 'system')->count(),
            'custom' => $this->buildRolesQuery($params, 'custom')->count(),
        ];
    }

    public function findRolePage(string $roleId, array $params): ?int
    {
        $query = $this->buildRolesQuery($params)
            ->select('roles.id')
            ->selectRaw(sprintf(
                'ROW_NUMBER() OVER (ORDER BY %s %s, roles.name asc, roles.id asc) as row_number',
                $this->roleSortColumn($params),
                $this->direction($params),
            ));

        $rowNumber = DB::query()
            ->fromSub($query, 'ranked_roles')
            ->where('id', $roleId)
            ->value('row_number');

        return $rowNumber === null ? null : (int) ceil((int) $rowNumber / $this->perPage($params));
    }

    public function listRolesForForm(): array
    {
        return Role::query()
            ->where('name', '!=', 'superadmin')
            ->with('parents:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'is_default', 'is_system'])
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'is_default' => $role->is_default,
                'is_system' => $role->is_system,
                'parent_ids' => $role->parents->pluck('id')->all(),
            ])
            ->all();
    }

    public function listPermissionCategoriesForForm(): array
    {
        return PermissionCategory::query()
            ->with(['permissions' => fn ($query) => $query->orderBy('name')])
            ->orderBy('module')
            ->orderBy('name')
            ->get()
            ->map(fn (PermissionCategory $category): array => [
                'id' => $category->id,
                'code' => $category->code,
                'module' => $category->module,
                'name' => $category->name,
                'is_system' => $category->is_system,
                'permissions_count' => $category->permissions->count(),
                'permissions' => $category->permissions->map(fn (Permission $permission): array => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'route_name' => $permission->route_name,
                    'action' => $permission->action,
                    'category_assignment_source' => $permission->category_assignment_source,
                ])->all(),
            ])
            ->all();
    }

    public function getRoleForm(Role $role): array
    {
        return [
            'role' => $this->mapRole($role->load(['parents', 'permissions', 'deniedPermissions'])),
            'roles' => $this->listRolesForForm(),
            'permissionCategories' => $this->listPermissionCategoriesForForm(),
        ];
    }

    public function createRole(array $data): Role
    {
        $name = Str::slug((string) $data['name'], '-');

        if ($name === '') {
            throw new \RuntimeException('Nama role tidak valid.');
        }

        if (Role::query()->where('name', $name)->exists()) {
            throw new \RuntimeException('Nama role sudah digunakan.');
        }

        $parentIds = $data['parent_role_ids'] ?? [];

        if ($parentIds === []) {
            throw new \RuntimeException('Role harus memiliki minimal satu parent.');
        }

        return DB::transaction(function () use ($name, $data, $parentIds): Role {
            $role = Role::create([
                'name' => $name,
                'guard_name' => 'web',
                'is_system' => false,
                'is_default' => false,
            ]);

            $this->syncRoleAccess($role, $parentIds, $data);

            return $role->fresh(['parents', 'permissions', 'deniedPermissions']);
        });
    }

    public function updateRole(Role $role, array $data): Role
    {
        if ($role->name === 'superadmin') {
            throw new \RuntimeException('Role superadmin tidak dapat diubah.');
        }

        return DB::transaction(function () use ($role, $data): Role {
            if (! $role->is_default) {
                $name = Str::slug((string) ($data['name'] ?? $role->name), '-');

                if ($name === '') {
                    throw new \RuntimeException('Nama role tidak valid.');
                }

                if (Role::query()->where('name', $name)->where('id', '!=', $role->id)->exists()) {
                    throw new \RuntimeException('Nama role sudah digunakan.');
                }

                $role->name = $name;
                $role->save();
            }

            $parentIds = $role->is_default
                ? $role->parents()->pluck('roles.id')->all()
                : ($data['parent_role_ids'] ?? []);

            $this->syncRoleAccess($role, $parentIds, $data);

            return $role->fresh(['parents', 'permissions', 'deniedPermissions']);
        });
    }

    public function deleteRole(Role $role): void
    {
        if ($role->is_default) {
            throw new \RuntimeException('Role default tidak dapat dihapus.');
        }

        if ($role->name === 'superadmin') {
            throw new \RuntimeException('Role superadmin tidak dapat dihapus.');
        }

        if ($role->users()->exists() || $role->children()->exists()) {
            throw new \RuntimeException('Role masih digunakan pengguna atau memiliki role turunan.');
        }

        $role->delete();
    }

    public function scanPermissions(): array
    {
        return $this->permissionScanner->scan();
    }

    public function listPermissionModules(): array
    {
        $categories = PermissionCategory::query()
            ->whereHas('permissions')
            ->withCount('permissions')
            ->get();

        return $categories
            ->groupBy('module')
            ->map(function (Collection $group, string $module): array {
                $systemCount = $group->where('is_system', true)->count();
                $customCount = $group->where('is_system', false)->count();

                return [
                    'module' => $module,
                    'categories_count' => $group->count(),
                    'permissions_count' => (int) $group->sum('permissions_count'),
                    'status' => $systemCount > 0 && $customCount > 0
                        ? 'Campuran'
                        : ($customCount > 0 ? 'Custom' : 'System'),
                ];
            })
            ->sortBy('module')
            ->values()
            ->all();
    }

    public function getPermissionModule(string $module): ?array
    {
        $categories = PermissionCategory::query()
            ->where('module', $module)
            ->whereHas('permissions')
            ->with(['permissions' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get();

        if ($categories->isEmpty()) {
            return null;
        }

        return [
            'module' => $module,
            'categories_count' => $categories->count(),
            'permissions_count' => $categories->sum(fn (PermissionCategory $category): int => $category->permissions->count()),
            'categories' => $categories->map(fn (PermissionCategory $category): array => [
                'id' => $category->id,
                'code' => $category->code,
                'module' => $category->module,
                'name' => $category->name,
                'is_system' => $category->is_system,
                'permissions_count' => $category->permissions->count(),
                'permissions' => $category->permissions->map(fn (Permission $permission): array => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'route_name' => $permission->route_name,
                    'action' => $permission->action,
                    'category_assignment_source' => $permission->category_assignment_source,
                ])->all(),
            ])->all(),
        ];
    }

    public function listPermissionCategoriesPaginated(array $params): LengthAwarePaginator
    {
        return $this->buildCategoriesQuery($params)
            ->paginate($this->perPage($params))
            ->withQueryString()
            ->through(fn (PermissionCategory $category): array => [
                'id' => $category->id,
                'code' => $category->code,
                'module' => $category->module,
                'name' => $category->name,
                'is_system' => $category->is_system,
                'permissions_count' => $category->permissions_count,
                'permissions' => $category->permissions->map(fn (Permission $permission): array => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'route_name' => $permission->route_name,
                    'action' => $permission->action,
                    'category_assignment_source' => $permission->category_assignment_source,
                ])->all(),
            ]);
    }

    public function findPermissionCategoryPage(string $categoryId, array $params): ?int
    {
        $query = $this->buildCategoriesQuery($params)
            ->select('permission_categories.id')
            ->selectRaw(sprintf(
                'ROW_NUMBER() OVER (ORDER BY %s %s, permission_categories.name asc, permission_categories.id asc) as row_number',
                $this->categorySortColumn($params),
                $this->direction($params),
            ));

        $rowNumber = DB::query()
            ->fromSub($query, 'ranked_categories')
            ->where('id', $categoryId)
            ->value('row_number');

        return $rowNumber === null ? null : (int) ceil((int) $rowNumber / $this->perPage($params));
    }

    public function updatePermissionCategory(PermissionCategory $category, array $data): PermissionCategory
    {
        $uncategorized = $this->uncategorizedCategory();
        $permissionIds = $data['permission_ids'] ?? null;

        if ($category->code === 'uncategorized' && is_array($permissionIds) && $permissionIds !== []) {
            throw new \RuntimeException('Kategori uncategorized tidak dapat menerima permission langsung.');
        }

        return DB::transaction(function () use ($category, $data, $uncategorized, $permissionIds): PermissionCategory {
            $category->update([
                'name' => $data['name'] ?? $category->name,
                'module' => $data['module'] ?? $category->module,
            ]);

            if (is_array($permissionIds) && $category->code !== 'uncategorized') {
                Permission::query()
                    ->where('permission_category_id', $category->id)
                    ->whereNotIn('id', $permissionIds)
                    ->update([
                        'permission_category_id' => $uncategorized->id,
                        'category_assignment_source' => 'manual',
                    ]);

                Permission::query()
                    ->whereIn('id', $permissionIds)
                    ->where('permission_category_id', $uncategorized->id)
                    ->update([
                        'permission_category_id' => $category->id,
                        'category_assignment_source' => 'manual',
                    ]);
            }

            return $category->fresh('permissions');
        });
    }

    public function assignPermission(PermissionCategory $category, Permission $permission): Permission
    {
        if ($category->code === 'uncategorized') {
            throw new \RuntimeException('Permission tidak dapat di-assign ke uncategorized.');
        }

        $permission->update([
            'permission_category_id' => $category->id,
            'category_assignment_source' => 'manual',
        ]);

        return $permission->fresh();
    }

    public function unassignPermission(Permission $permission): Permission
    {
        $permission->update([
            'permission_category_id' => $this->uncategorizedCategory()->id,
            'category_assignment_source' => 'manual',
        ]);

        return $permission->fresh();
    }

    public function resetPermissionCategory(Permission $permission): Permission
    {
        $data = $this->permissionScanner->categoryData((string) $permission->route_name);
        $category = PermissionCategory::firstOrCreate(
            ['code' => $data['code']],
            [
                'name' => $data['name'],
                'module' => $data['module'],
                'is_system' => true,
            ],
        );

        $permission->update([
            'permission_category_id' => $category->id,
            'category_assignment_source' => 'scanner',
        ]);

        return $permission->fresh();
    }

    public function renameModule(string $currentModule, string $newModule): int
    {
        return PermissionCategory::query()
            ->where('module', $currentModule)
            ->update(['module' => $newModule]);
    }

    public function listUncategorizedPermissions(): array
    {
        return $this->uncategorizedCategory()
            ->permissions()
            ->orderBy('name')
            ->get()
            ->map(fn (Permission $permission): array => [
                'id' => $permission->id,
                'name' => $permission->name,
                'route_name' => $permission->route_name,
                'action' => $permission->action,
            ])
            ->all();
    }

    public function effectivePermissionNames(Pengguna $pengguna): array
    {
        if ($pengguna->hasRole('superadmin')) {
            return Permission::query()->pluck('name')->all();
        }

        $names = $pengguna->getDirectPermissions()->pluck('name')->all();

        foreach ($pengguna->roles()->with('permissions')->get() as $role) {
            $names = array_merge($names, $this->effectivePermissions($role)->pluck('name')->all());
        }

        return array_values(array_unique($names));
    }

    public function userHasPermission(Pengguna $pengguna, string $permission): bool
    {
        return in_array($permission, $this->effectivePermissionNames($pengguna), true);
    }

    public function userHasRole(Pengguna $pengguna, string $role): bool
    {
        return $pengguna->hasRole($role);
    }

    public function refreshPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return Collection<int, Permission>
     */
    public function effectivePermissions(Role $role): Collection
    {
        if ($role->name === 'superadmin') {
            return Permission::query()->get();
        }

        $roles = $this->ancestorRoles($role)->push($role);
        $roles = Role::query()->with('permissions')->whereIn('id', $roles->pluck('id'))->get();

        $permissionIds = $roles
            ->flatMap(fn (Role $currentRole) => $currentRole->permissions->pluck('id'))
            ->unique()
            ->values();

        $denied = DB::table('role_permission_denials')
            ->whereIn('role_id', $roles->pluck('id'))
            ->pluck('permission_id');

        return Permission::query()
            ->whereIn('id', $permissionIds)
            ->whereNotIn('id', $denied)
            ->get();
    }

    private function buildUsersQuery(array $params, ?string $tab = null): Builder
    {
        $tab = $tab ?? (in_array($params['tab'] ?? null, ['admin', 'peserta', 'custom'], true)
            ? $params['tab']
            : 'admin');
        $search = trim((string) ($params['q'] ?? ''));
        $status = $params['f_status'] ?? null;
        $programStudiId = $params['f_program_studi_id'] ?? null;
        $roleName = $params['f_role_names'] ?? null;
        $jenisPeserta = $params['f_jenis_peserta'] ?? null;

        $query = Pengguna::query()
            ->with([
                'roles:id,name',
                'programStudis:id,nama',
                'profilPeserta:id,pengguna_id,jenis_peserta,nomor_identitas,institusi,fakultas_id,program_studi_id,tahun_mulai,terverifikasi',
            ])
            ->whereDoesntHave('roles', fn (Builder $roleQuery) => $roleQuery->where('name', 'superadmin'))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.username', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%");
                });
            })
            ->when(filled($roleName), fn (Builder $query) => $query->whereHas(
                'roles',
                fn (Builder $roleQuery) => $roleQuery->where('roles.name', $roleName),
            ))
            ->when(in_array((string) $status, ['true', 'false'], true), fn (Builder $query) => $query->where(
                'users.status',
                (string) $status === 'true',
            ))
            ->when(filled($programStudiId), fn (Builder $query) => $query->whereHas(
                'programStudis',
                fn (Builder $programStudiQuery) => $programStudiQuery->where('program_studi_id', $programStudiId),
            ))
            ->when(filled($jenisPeserta), fn (Builder $query) => $query->whereHas(
                'profilPeserta',
                fn (Builder $profilQuery) => $profilQuery->where('jenis_peserta', $jenisPeserta),
            ));

        if ($tab === 'custom') {
            $systemRoleIds = array_merge($this->roleBranchIds('admin'), $this->roleBranchIds('peserta'));
            $query->whereHas('roles', fn (Builder $roleQuery) => $roleQuery->whereNotIn('roles.id', $systemRoleIds));
        } else {
            $roleIds = $this->roleBranchIds($tab);
            $query->when($roleIds === [], fn (Builder $query) => $query->whereRaw('1 = 0'));
            $query->when($roleIds !== [], fn (Builder $query) => $query->whereHas(
                'roles',
                fn (Builder $roleQuery) => $roleQuery->whereIn('roles.id', $roleIds),
            ));
        }

        return $query
            ->orderBy($this->userSortColumn($params), $this->direction($params))
            ->orderBy('users.name')
            ->orderBy('users.id');
    }

    /**
     * @return array<int, string>
     */
    private function roleBranchIds(string $roleName): array
    {
        if (array_key_exists($roleName, $this->roleBranchCache)) {
            return $this->roleBranchCache[$roleName];
        }

        $ids = Role::query()->where('name', $roleName)->pluck('id')->all();
        $frontier = $ids;

        while ($frontier !== []) {
            $children = DB::table('role_parents')
                ->whereIn('parent_role_id', $frontier)
                ->pluck('role_id')
                ->diff($ids)
                ->values()
                ->all();

            if ($children === []) {
                break;
            }

            $ids = array_values(array_unique(array_merge($ids, $children)));
            $frontier = $children;
        }

        return $this->roleBranchCache[$roleName] = $ids;
    }

    /**
     * @param  array<int, string>  $roleIds
     * @return Collection<int, Role>
     */
    private function rolesFromIds(array $roleIds): Collection
    {
        return Role::query()->whereIn('id', $roleIds)->get();
    }

    /**
     * @param  Collection<int, Role>  $roles
     */
    private function guardRoleSelection(Collection $roles): void
    {
        if ($roles->isEmpty()) {
            throw new \RuntimeException('Minimal satu role harus dipilih.');
        }

        if ($roles->contains('name', 'superadmin')) {
            throw new \RuntimeException('Role superadmin tidak dapat diberikan dari pengelolaan pengguna.');
        }

        if ($roles->contains('name', 'peserta') && $roles->count() > 1) {
            throw new \RuntimeException('Role peserta tidak dapat digabung dengan role lain.');
        }
    }

    private function syncUserProgramStudi(Pengguna $pengguna, ?string $programStudiId): void
    {
        $pengguna->programStudis()->sync(
            filled($programStudiId) ? [$programStudiId] : [],
        );
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function buildRolesQuery(array $params, ?string $tab = null): Builder
    {
        $tab = $tab ?? (in_array($params['tab'] ?? null, ['default', 'system', 'custom'], true)
            ? $params['tab']
            : 'default');
        $search = trim((string) ($params['q'] ?? ''));

        return Role::query()
            ->where('name', '!=', 'superadmin')
            ->withCount('users')
            ->with(['parents:id,name', 'permissions:id,name', 'deniedPermissions:id,name'])
            ->when($search !== '', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
            ->when($tab === 'default', fn (Builder $query) => $query->where('is_default', true))
            ->when($tab === 'system', fn (Builder $query) => $query->where('is_system', true)->where('is_default', false))
            ->when($tab === 'custom', fn (Builder $query) => $query->where('is_system', false)->where('is_default', false))
            ->orderBy($this->roleSortColumn($params), $this->direction($params))
            ->orderBy('roles.name')
            ->orderBy('roles.id');
    }

    /**
     * @return array<string, mixed>
     */
    private function mapUser(Pengguna $pengguna): array
    {
        return [
            'id' => $pengguna->id,
            'name' => $pengguna->name,
            'username' => $pengguna->username,
            'email' => $pengguna->email,
            'status' => (bool) $pengguna->status,
            'last_login' => $pengguna->last_login?->format('Y-m-d H:i'),
            'roles' => $pengguna->roles->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
            ])->all(),
            'role_names' => $pengguna->roles->pluck('name')->all(),
            'jenis_peserta' => $pengguna->profilPeserta?->jenis_peserta?->value,
            'jenis_peserta_label' => $pengguna->profilPeserta?->jenis_peserta?->label(),
            'nomor_identitas' => $pengguna->profilPeserta?->nomor_identitas,
            'institusi' => $pengguna->profilPeserta?->institusi,
            'fakultas_id' => $pengguna->profilPeserta?->fakultas_id,
            'tahun_mulai' => $pengguna->profilPeserta?->tahun_mulai,
            'program_studi_id' => $pengguna->profilPeserta !== null
                ? $pengguna->profilPeserta->program_studi_id
                : $pengguna->programStudis->first()?->id,
            'program_studi_nama' => $pengguna->programStudis->first()?->nama,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapRole(Role $role): array
    {
        $effective = $this->effectivePermissions($role);
        $ancestorIds = $this->ancestorRoles($role)->pluck('id')->all();
        $inheritedIds = $ancestorIds === []
            ? []
            : DB::table('role_has_permissions')
                ->whereIn('role_id', $ancestorIds)
                ->pluck('permission_id')
                ->unique()
                ->values()
                ->all();

        return [
            'id' => $role->id,
            'name' => $role->name,
            'is_system' => $role->is_system,
            'is_default' => $role->is_default,
            'users_count' => $role->users_count ?? $role->users()->count(),
            'parents' => $role->parents->pluck('name')->all(),
            'parent_ids' => $role->parents->pluck('id')->all(),
            'grant_permission_ids' => $role->permissions->pluck('id')->all(),
            'deny_permission_ids' => $role->deniedPermissions->pluck('id')->all(),
            'inherited_permission_ids' => $inheritedIds,
            'effective_permission_ids' => $effective->pluck('id')->values()->all(),
            'effective_permissions_count' => $effective->count(),
        ];
    }

    /**
     * @param  array<int, string>  $parentIds
     * @param  array<string, mixed>  $data
     */
    private function syncRoleAccess(Role $role, array $parentIds, array $data): void
    {
        $parents = Role::query()->whereIn('id', $parentIds)->get();

        if ($parents->contains('name', 'superadmin')) {
            throw new \RuntimeException('Role superadmin tidak dapat digunakan dalam inheritance.');
        }

        if (in_array($role->id, $parentIds, true)) {
            throw new \RuntimeException('Role tidak boleh menjadi parent bagi dirinya sendiri.');
        }

        foreach ($parents as $parent) {
            if ($this->ancestorRoles($parent)->contains(fn (Role $ancestor): bool => $ancestor->id === $role->id)) {
                throw new \RuntimeException('Inheritance role membentuk siklus yang tidak valid.');
            }
        }

        $role->parents()->sync($parentIds);
        $role->syncPermissions($data['grant_permission_ids'] ?? []);
        $role->deniedPermissions()->sync($data['deny_permission_ids'] ?? []);
    }

    /**
     * @return Collection<int, Role>
     */
    private function ancestorRoles(Role $role): Collection
    {
        $collected = new Collection;
        $frontier = $role->parents()->get();
        $visited = [];

        while ($frontier->isNotEmpty()) {
            $next = new Collection;

            foreach ($frontier as $parent) {
                if (isset($visited[$parent->id]) || $parent->name === 'superadmin') {
                    continue;
                }

                $visited[$parent->id] = true;
                $collected->push($parent);
                $next = $next->merge($parent->parents()->get());
            }

            $frontier = $next;
        }

        return $collected;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function buildCategoriesQuery(array $params): Builder
    {
        $search = trim((string) ($params['q'] ?? ''));
        $module = $params['f_module'] ?? null;

        return PermissionCategory::query()
            ->whereHas('permissions')
            ->withCount('permissions')
            ->with(['permissions' => fn ($query) => $query->orderBy('name')])
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $inner) use ($search): void {
                $inner->where('module', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->when(filled($module), fn (Builder $query) => $query->where('module', $module))
            ->orderBy($this->categorySortColumn($params), $this->direction($params))
            ->orderBy('permission_categories.name')
            ->orderBy('permission_categories.id');
    }

    private function uncategorizedCategory(): PermissionCategory
    {
        return PermissionCategory::firstOrCreate(
            ['code' => 'uncategorized'],
            [
                'name' => 'Uncategorized',
                'module' => 'System',
                'is_system' => true,
            ],
        );
    }

    private function perPage(array $params): int
    {
        $perPage = (int) ($params['per_page'] ?? 10);

        return in_array($perPage, self::PER_PAGE, true) ? $perPage : 10;
    }

    private function direction(array $params): string
    {
        return ($params['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
    }

    private function userSortColumn(array $params): string
    {
        $sort = $params['sort'] ?? 'name';

        if (! in_array($sort, self::USER_SORTS, true)) {
            $sort = 'name';
        }

        return 'users.'.$sort;
    }

    private function roleSortColumn(array $params): string
    {
        $sort = $params['sort'] ?? 'name';

        if (! in_array($sort, self::ROLE_SORTS, true)) {
            $sort = 'name';
        }

        return $sort === 'users_count' ? 'users_count' : 'roles.'.$sort;
    }

    private function categorySortColumn(array $params): string
    {
        $sort = $params['sort'] ?? 'module';

        if (! in_array($sort, self::CATEGORY_SORTS, true)) {
            $sort = 'module';
        }

        return $sort === 'permissions_count' ? 'permissions_count' : 'permission_categories.'.$sort;
    }
}
