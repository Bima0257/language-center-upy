<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\StudentProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(TagSeeder::class);

        $this->createUsers();

        $this->call(ExamSeeder::class);
    }

    private function createUsers(): void
    {
        $superadmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@toefl.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $superadmin->assignRole('superadmin');

        $admin = User::create([
            'name' => 'Admin Akademik',
            'email' => 'admin@toefl.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        $proctor = User::create([
            'name' => 'Proctor Ujian',
            'email' => 'proctor@toefl.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $proctor->assignRole('proctor');

        $instructor = User::create([
            'name' => 'Instruktur',
            'email' => 'instructor@toefl.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $instructor->assignRole('instructor');

        $student = User::create([
            'name' => 'Peserta Demo',
            'email' => 'student@toefl.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $student->assignRole('student');

        StudentProfile::create([
            'user_id' => $student->id,
            'nim' => '2200010001',
            'faculty' => 'Fakultas Keguruan dan Ilmu Pendidikan',
            'department' => 'Pendidikan Bahasa Inggris',
            'batch_year' => 2022,
            'identity_photo' => null,
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => $admin->id,
        ]);
    }
}
