<?php

use App\Models\Permission;
use App\Models\Role;
use App\Modules\Identity\Services\PermissionScanner;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        app(PermissionScanner::class)->scan();

        $permissions = Permission::query()
            ->where('route_name', 'like', 'admin.data-induk.aturan-penilaian.%')
            ->where('guard_name', 'web')
            ->get();

        foreach (['admin', 'superadmin'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->givePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = Permission::query()
            ->where('route_name', 'like', 'admin.data-induk.aturan-penilaian.%')
            ->where('guard_name', 'web')
            ->get();

        foreach (['admin', 'superadmin'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->revokePermissionTo($permissions);
        }

        Permission::query()
            ->whereIn('id', $permissions->pluck('id'))
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
