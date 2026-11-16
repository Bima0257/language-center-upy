<?php

namespace App\Modules\Identity\Contracts;

use App\Models\Pengguna;
use App\Models\Permission;
use App\Models\PermissionCategory;
use App\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IdentityContract
{
    /**
     * @param  array<string, mixed>  $params
     */
    public function listUsersPaginated(array $params): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, int>
     */
    public function countUsersByTab(array $params): array;

    /**
     * @param  array<string, mixed>  $params
     */
    public function findUserPage(string $userId, array $params): ?int;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listRoleOptions(): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listProgramStudiOptions(): array;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createUser(array $data): Pengguna;

    /**
     * @param  array<int, array<string, mixed>>  $users
     */
    public function createUserBulk(array $users): int;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateUser(Pengguna $pengguna, array $data): Pengguna;

    public function deleteUser(Pengguna $pengguna): void;

    /**
     * @param  array<string, mixed>  $params
     */
    public function listRolesPaginated(array $params): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, int>
     */
    public function countRolesByTab(array $params): array;

    /**
     * @param  array<string, mixed>  $params
     */
    public function findRolePage(string $roleId, array $params): ?int;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listRolesForForm(): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listPermissionCategoriesForForm(): array;

    /**
     * @return array<string, mixed>
     */
    public function getRoleForm(Role $role): array;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createRole(array $data): Role;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateRole(Role $role, array $data): Role;

    public function deleteRole(Role $role): void;

    /**
     * @return array<string, mixed>
     */
    public function scanPermissions(): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listPermissionModules(): array;

    /**
     * @return array<string, mixed>|null
     */
    public function getPermissionModule(string $module): ?array;

    /**
     * @param  array<string, mixed>  $params
     */
    public function listPermissionCategoriesPaginated(array $params): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $params
     */
    public function findPermissionCategoryPage(string $categoryId, array $params): ?int;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePermissionCategory(PermissionCategory $category, array $data): PermissionCategory;

    public function assignPermission(PermissionCategory $category, Permission $permission): Permission;

    public function unassignPermission(Permission $permission): Permission;

    public function resetPermissionCategory(Permission $permission): Permission;

    public function renameModule(string $currentModule, string $newModule): int;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listUncategorizedPermissions(): array;

    /**
     * @return array<int, string>
     */
    public function effectivePermissionNames(Pengguna $pengguna): array;

    public function userHasPermission(Pengguna $pengguna, string $permission): bool;

    public function userHasRole(Pengguna $pengguna, string $role): bool;

    public function refreshPermissionCache(): void;
}
