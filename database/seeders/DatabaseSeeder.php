<?php

namespace Database\Seeders;

use App\Models\Fakultas;
use App\Models\Pengguna;
use App\Models\ProfilMahasiswa;
use App\Models\ProgramStudi;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(DataIndukSeeder::class);
        $this->call(KebijakanMateriSoalSeeder::class);

        $this->buatPengguna();

        $this->call(UjianSeeder::class);
    }

    private function buatPengguna(): void
    {
        $superadmin = Pengguna::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@toefl.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'aktif' => true,
        ]);
        $superadmin->assignRole('superadmin');

        $admin = Pengguna::create([
            'name' => 'Admin Akademik',
            'email' => 'admin@toefl.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'aktif' => true,
        ]);
        $admin->assignRole('admin');

        $proctor = Pengguna::create([
            'name' => 'Proctor Ujian',
            'email' => 'proctor@toefl.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'aktif' => true,
        ]);
        $proctor->assignRole('proctor');

        $instructor = Pengguna::create([
            'name' => 'Instruktur',
            'email' => 'instructor@toefl.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'aktif' => true,
        ]);
        $instructor->assignRole('instructor');

        $mahasiswa = Pengguna::create([
            'name' => 'Peserta Demo',
            'email' => 'student@toefl.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'aktif' => true,
        ]);
        $mahasiswa->assignRole('student');

        $fkip = Fakultas::where('kode', 'FKIP')->first();
        $pbi = ProgramStudi::where('kode', 'PBI')->first();

        if ($fkip && $pbi) {
            ProfilMahasiswa::create([
                'pengguna_id' => $mahasiswa->id,
                'nim' => '2200010001',
                'fakultas_id' => $fkip->id,
                'program_studi_id' => $pbi->id,
                'tahun_angkatan' => 2022,
                'foto_identitas' => null,
                'terverifikasi' => true,
                'diverifikasi_pada' => now(),
                'verifikator_id' => $admin->id,
            ]);
        }
    }
}
