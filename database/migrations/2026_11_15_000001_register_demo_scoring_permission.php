<?php

use App\Models\Permission;
use App\Models\PermissionCategory;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private string $permissionName = 'admin.demo-ujian.skor';

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $category = PermissionCategory::firstOrCreate(
            ['code' => 'admin.demo-ujian'],
            [
                'name' => 'Demo Ujian',
                'module' => 'Ujian',
                'is_system' => true,
            ],
        );

        $permission = Permission::firstOrCreate(
            ['name' => $this->permissionName, 'guard_name' => 'web'],
            [
                'permission_category_id' => $category->id,
                'route_name' => $this->permissionName,
                'action' => 'skor',
                'is_system' => true,
                'category_assignment_source' => 'manual',
                'last_scanned_at' => now(),
            ],
        );

        foreach (['admin', 'superadmin'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::query()
            ->where('name', $this->permissionName)
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
