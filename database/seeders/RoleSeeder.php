<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Modules\Identity\Contracts\IdentityContract;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        app(IdentityContract::class)->scanPermissions();

        $superadmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $superadmin->update(['is_system' => true, 'is_default' => false]);

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->update(['is_system' => true, 'is_default' => true]);

        $peserta = Role::firstOrCreate(['name' => 'peserta', 'guard_name' => 'web']);
        $peserta->update(['is_system' => true, 'is_default' => true]);

        $adminPermissions = Permission::query()
            ->whereNotNull('route_name')
            ->where(function ($query): void {
                $query->where(function ($query): void {
                    $query->where('route_name', 'like', 'admin.%')
                        ->where('route_name', 'not like', 'admin.identity.%');
                })->orWhere('route_name', 'like', 'pengawas.%')
                    ->orWhere('route_name', 'like', 'pustaka-soal.%');
            })
            ->pluck('name')
            ->all();

        $pesertaPermissions = Permission::query()
            ->where('route_name', 'like', 'ujian.%')
            ->pluck('name')
            ->all();

        $admin->syncPermissions($adminPermissions);
        $peserta->syncPermissions($pesertaPermissions);

        Permission::query()->whereNull('route_name')->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
