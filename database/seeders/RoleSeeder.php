<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissionNames = [
            'user.view',
            'user.create',
            'user.edit',
            'user.delete',

            'exam.create',
            'exam.edit',
            'exam.delete',
            'exam.view',
            'exam.manage',

            'exam_session.view',
            'exam_session.take',
            'exam_session.monitor',
            'exam_session.terminate',

            'schedule.create',
            'schedule.edit',
            'schedule.delete',
            'schedule.view',

            'question.create',
            'question.edit',
            'question.delete',
            'question.view',

            'review.violations',
            'review.decide',

            'report.view',
            'report.export',

            'speaking_score.create',
            'writing_score.create',

            'appeal.create',
            'appeal.review',
            'appeal.view',

            'settings.manage',

            'master_data.view',
            'master_data.manage',
            'certificate.view',
            'certificate.create',
            'certificate.delete',
            'audit_log.view',
            'analytics.view',
            'components.view',
        ];

        foreach ($permissionNames as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $permissionNames)
            ->get();

        $superadmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $superadmin->syncPermissions($permissions);

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions([
            'user.view', 'user.create', 'user.edit',
            'exam.view', 'exam.manage', 'exam.create', 'exam.edit', 'exam.delete',
            'exam_session.view',
            'exam_session.monitor',
            'exam_session.terminate',
            'schedule.view', 'schedule.create', 'schedule.edit', 'schedule.delete',
            'question.view', 'question.create', 'question.edit', 'question.delete',
            'review.violations',
            'review.decide',
            'report.view', 'report.export',
            'speaking_score.create',
            'writing_score.create',
            'settings.manage',
            'master_data.view', 'master_data.manage',
            'certificate.view', 'certificate.create', 'certificate.delete',
            'audit_log.view',
            'analytics.view',
            'components.view',
        ]);

        $proctor = Role::firstOrCreate(['name' => 'proctor', 'guard_name' => 'web']);
        $proctor->syncPermissions([
            'exam_session.view',
            'exam_session.monitor',
            'exam_session.terminate',
            'review.violations',
            'user.view',
        ]);

        $instructor = Role::firstOrCreate(['name' => 'instructor', 'guard_name' => 'web']);
        $instructor->syncPermissions([
            'question.create', 'question.edit', 'question.delete', 'question.view',
            'exam.view',
            'user.view',
            'speaking_score.create',
            'writing_score.create',
        ]);

        $student = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $student->syncPermissions([
            'exam.view',
            'exam_session.view',
            'exam_session.take',
        ]);
    }
}
