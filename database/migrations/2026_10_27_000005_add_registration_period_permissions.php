<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissions = [
        'registration_period.view',
        'registration_period.create',
        'registration_period.edit',
        'registration_period.delete',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $this->permissions)
            ->get();

        foreach (['admin', 'superadmin'] as $roleName) {
            Role::where('name', $roleName)
                ->where('guard_name', 'web')
                ->first()?->givePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $this->permissions)
            ->get();

        foreach (['admin', 'superadmin'] as $roleName) {
            Role::where('name', $roleName)
                ->where('guard_name', 'web')
                ->first()?->revokePermissionTo($permissions);
        }

        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $this->permissions)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
