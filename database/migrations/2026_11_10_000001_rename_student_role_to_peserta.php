<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::transaction(function (): void {
            $studentRoleId = DB::table('roles')
                ->where('name', 'student')
                ->where('guard_name', 'web')
                ->value('id');
            $pesertaRoleId = DB::table('roles')
                ->where('name', 'peserta')
                ->where('guard_name', 'web')
                ->value('id');

            if ($studentRoleId && ! $pesertaRoleId) {
                DB::table('roles')
                    ->where('id', $studentRoleId)
                    ->update(['name' => 'peserta']);
            } elseif ($studentRoleId && $pesertaRoleId) {
                DB::table('model_has_roles as old_roles')
                    ->where('old_roles.role_id', $studentRoleId)
                    ->whereExists(function ($query) use ($pesertaRoleId): void {
                        $query->select(DB::raw('1'))
                            ->from('model_has_roles as current_roles')
                            ->whereColumn('current_roles.model_id', 'old_roles.model_id')
                            ->whereColumn('current_roles.model_type', 'old_roles.model_type')
                            ->where('current_roles.role_id', $pesertaRoleId);
                    })
                    ->delete();

                DB::table('model_has_roles')
                    ->where('role_id', $studentRoleId)
                    ->update(['role_id' => $pesertaRoleId]);

                DB::table('role_has_permissions')
                    ->where('role_id', $studentRoleId)
                    ->delete();
                DB::table('roles')
                    ->where('id', $studentRoleId)
                    ->delete();
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::table('roles')
            ->where('name', 'peserta')
            ->where('guard_name', 'web')
            ->update(['name' => 'student']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
