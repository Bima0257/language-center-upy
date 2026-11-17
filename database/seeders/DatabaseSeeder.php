<?php

namespace Database\Seeders;

use App\Models\Fakultas;
use App\Models\Pengguna;
use App\Models\ProfilPeserta;
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

        $this->call(SoalEptSeeder::class);
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

        $mahasiswa = Pengguna::create([
            'name' => 'Peserta Demo',
            'email' => 'peserta@toefl.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'aktif' => true,
        ]);
        $mahasiswa->assignRole('peserta');

        $fkip = Fakultas::where('kode', 'FKIP')->first();
        $pbi = ProgramStudi::where('kode', 'PBI')->first();

        if ($fkip && $pbi) {
            ProfilPeserta::create([
                'pengguna_id' => $mahasiswa->id,
                'jenis_peserta' => 'mahasiswa',
                'nomor_identitas' => '2200010001',
                'fakultas_id' => $fkip->id,
                'program_studi_id' => $pbi->id,
                'tahun_mulai' => 2022,
                'foto_identitas' => null,
                'terverifikasi' => true,
                'diverifikasi_pada' => now(),
                'verifikator_id' => $admin->id,
            ]);

            $mahasiswa->programStudis()->sync([$pbi->id]);

            $dosen = Pengguna::firstOrCreate(
                ['email' => 'dosen@toefl.test'],
                [
                    'name' => 'Dosen Demo',
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                    'aktif' => true,
                ],
            );
            $dosen->assignRole('peserta');

            ProfilPeserta::firstOrCreate(
                ['pengguna_id' => $dosen->id],
                [
                    'jenis_peserta' => 'dosen',
                    'nomor_identitas' => 'NIDN-2200010002',
                    'fakultas_id' => $fkip->id,
                    'program_studi_id' => $pbi->id,
                    'tahun_mulai' => 2015,
                    'foto_identitas' => null,
                    'terverifikasi' => true,
                    'diverifikasi_pada' => now(),
                    'verifikator_id' => $admin->id,
                ],
            );
            $dosen->programStudis()->sync([$pbi->id]);

            $umum = Pengguna::firstOrCreate(
                ['email' => 'umum@toefl.test'],
                [
                    'name' => 'Peserta Umum Demo',
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                    'aktif' => true,
                ],
            );
            $umum->assignRole('peserta');

            ProfilPeserta::firstOrCreate(
                ['pengguna_id' => $umum->id],
                [
                    'jenis_peserta' => 'umum',
                    'nomor_identitas' => 'NIK-3400000000000001',
                    'institusi' => 'Masyarakat Umum',
                    'fakultas_id' => null,
                    'program_studi_id' => null,
                    'tahun_mulai' => null,
                    'foto_identitas' => null,
                    'terverifikasi' => true,
                    'diverifikasi_pada' => now(),
                    'verifikator_id' => $admin->id,
                ],
            );
        }
    }
}
