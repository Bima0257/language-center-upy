<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissions = [
        'exam_registration.view',
        'exam_registration.apply',
        'exam_registration.manage',
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
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->givePermissionTo($permissions);
        }

        Role::where('name', 'student')
            ->where('guard_name', 'web')
            ->first()?->givePermissionTo('exam_registration.apply');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $this->permissions)
            ->get();

        foreach (['admin', 'superadmin', 'student'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->revokePermissionTo($permissions);
        }

        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $this->permissions)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
