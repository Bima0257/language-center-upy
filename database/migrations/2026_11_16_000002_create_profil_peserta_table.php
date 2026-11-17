<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profil_peserta', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('pengguna_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();
            $table->enum('jenis_peserta', ['mahasiswa', 'dosen', 'umum'])->default('mahasiswa');
            $table->string('nomor_identitas', 30)->nullable();
            $table->string('institusi', 150)->nullable();
            $table->foreignUuid('fakultas_id')
                ->nullable()
                ->constrained('fakultas')
                ->restrictOnDelete();
            $table->foreignUuid('program_studi_id')
                ->nullable()
                ->constrained('program_studi')
                ->restrictOnDelete();
            $table->integer('tahun_mulai')->nullable();
            $table->string('foto_identitas')->nullable();
            $table->boolean('terverifikasi')->default(false);
            $table->timestamp('diverifikasi_pada')->nullable();
            $table->foreignUuid('verifikator_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamps();

            $table->index('jenis_peserta');
            $table->index('nomor_identitas');
            $table->index(['jenis_peserta', 'terverifikasi']);
        });

        $this->salinProfilMahasiswa();

        Schema::dropIfExists('profil_mahasiswa');
    }

    public function down(): void
    {
        Schema::create('profil_mahasiswa', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('pengguna_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('nim', 20)->nullable()->index();
            $table->foreignUuid('fakultas_id')
                ->constrained('fakultas')
                ->restrictOnDelete();
            $table->foreignUuid('program_studi_id')
                ->constrained('program_studi')
                ->restrictOnDelete();
            $table->integer('tahun_angkatan')->nullable();
            $table->string('foto_identitas')->nullable();
            $table->boolean('terverifikasi')->default(false);
            $table->timestamp('diverifikasi_pada')->nullable();
            $table->foreignUuid('verifikator_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamps();
        });

        $this->kembalikanProfilMahasiswa();

        Schema::dropIfExists('profil_peserta');
    }

    private function salinProfilMahasiswa(): void
    {
        DB::table('profil_mahasiswa')
            ->select([
                'id',
                'pengguna_id',
                'nim',
                'fakultas_id',
                'program_studi_id',
                'tahun_angkatan',
                'foto_identitas',
                'terverifikasi',
                'diverifikasi_pada',
                'verifikator_id',
                'catatan_verifikasi',
                'created_at',
                'updated_at',
            ])
            ->chunkById(500, function ($profiles): void {
                $baris = $profiles->map(fn ($profil): array => [
                    'id' => $profil->id,
                    'pengguna_id' => $profil->pengguna_id,
                    'jenis_peserta' => 'mahasiswa',
                    'nomor_identitas' => $profil->nim,
                    'institusi' => null,
                    'fakultas_id' => $profil->fakultas_id,
                    'program_studi_id' => $profil->program_studi_id,
                    'tahun_mulai' => $profil->tahun_angkatan,
                    'foto_identitas' => $profil->foto_identitas,
                    'terverifikasi' => $profil->terverifikasi,
                    'diverifikasi_pada' => $profil->diverifikasi_pada,
                    'verifikator_id' => $profil->verifikator_id,
                    'catatan_verifikasi' => $profil->catatan_verifikasi,
                    'created_at' => $profil->created_at,
                    'updated_at' => $profil->updated_at,
                ])->all();

                DB::table('profil_peserta')->insertOrIgnore($baris);
            }, 'id');
    }

    private function kembalikanProfilMahasiswa(): void
    {
        DB::table('profil_peserta')
            ->select([
                'id',
                'pengguna_id',
                'nomor_identitas',
                'fakultas_id',
                'program_studi_id',
                'tahun_mulai',
                'foto_identitas',
                'terverifikasi',
                'diverifikasi_pada',
                'verifikator_id',
                'catatan_verifikasi',
                'created_at',
                'updated_at',
            ])
            ->where('jenis_peserta', 'mahasiswa')
            ->whereNotNull('fakultas_id')
            ->whereNotNull('program_studi_id')
            ->chunkById(500, function ($profiles): void {
                $baris = $profiles->map(fn ($profil): array => [
                    'id' => $profil->id,
                    'pengguna_id' => $profil->pengguna_id,
                    'nim' => $profil->nomor_identitas,
                    'fakultas_id' => $profil->fakultas_id,
                    'program_studi_id' => $profil->program_studi_id,
                    'tahun_angkatan' => $profil->tahun_mulai,
                    'foto_identitas' => $profil->foto_identitas,
                    'terverifikasi' => $profil->terverifikasi,
                    'diverifikasi_pada' => $profil->diverifikasi_pada,
                    'verifikator_id' => $profil->verifikator_id,
                    'catatan_verifikasi' => $profil->catatan_verifikasi,
                    'created_at' => $profil->created_at,
                    'updated_at' => $profil->updated_at,
                ])->all();

                DB::table('profil_mahasiswa')->insertOrIgnore($baris);
            }, 'id');
    }
};
