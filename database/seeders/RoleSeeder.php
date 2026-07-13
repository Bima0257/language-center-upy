<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'user.view',
            'user.create',
            'user.edit',
            'user.delete',

            'exam.create',
            'exam.edit',
            'exam.delete',
            'exam.view',

            'exam_session.view',
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
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superadmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $superadmin->givePermissionTo(Permission::all());

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo([
            'user.view', 'user.create', 'user.edit',
            'exam.view', 'exam.create', 'exam.edit',
            'schedule.view', 'schedule.create', 'schedule.edit', 'schedule.delete',
            'exam_session.view',
            'review.violations', 'review.decide',
            'report.view', 'report.export',
            'settings.manage',
        ]);

        $proctor = Role::firstOrCreate(['name' => 'proctor', 'guard_name' => 'web']);
        $proctor->givePermissionTo([
            'exam_session.view',
            'exam_session.monitor',
            'exam_session.terminate',
            'review.violations',
            'user.view',
        ]);

        $instructor = Role::firstOrCreate(['name' => 'instructor', 'guard_name' => 'web']);
        $instructor->givePermissionTo([
            'question.create', 'question.edit', 'question.delete', 'question.view',
            'exam.view',
            'user.view',
            'speaking_score.create',
            'writing_score.create',
        ]);

        $student = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $student->givePermissionTo([
            'exam.view',
            'exam_session.view',
        ]);
    }
}
