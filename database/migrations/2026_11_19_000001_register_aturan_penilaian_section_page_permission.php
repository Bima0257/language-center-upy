<?php

use App\Models\Permission;
use App\Models\Role;
use App\Modules\Identity\Services\PermissionScanner;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private string $routeName = 'admin.data-induk.aturan-penilaian.skema.section.ubah';

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        app(PermissionScanner::class)->scan();

        $permission = Permission::query()
            ->where('route_name', $this->routeName)
            ->where('guard_name', 'web')
            ->first();

        if ($permission !== null) {
            foreach (['admin', 'superadmin'] as $roleName) {
                $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
                $role?->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::query()
            ->where('route_name', $this->routeName)
            ->where('guard_name', 'web')
            ->first();

        if ($permission !== null) {
            foreach (['admin', 'superadmin'] as $roleName) {
                $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
                $role?->revokePermissionTo($permission);
            }

            $permission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
