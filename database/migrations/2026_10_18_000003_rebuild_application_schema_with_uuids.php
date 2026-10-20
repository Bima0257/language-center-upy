<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            $this->dropApplicationSchema();
            $this->createApplicationSchema(true);
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            $this->dropApplicationSchema();
            $this->createApplicationSchema(false);
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private function dropApplicationSchema(): void
    {
        foreach ([
            'sertifikat',
            'catatan_pelanggaran',
            'jawaban',
            'interpretasi_skor',
            'aturan_penilaian',
            'sesi_ujian',
            'slot_ujian',
            'periode_ujian',
            'bagian_soal_ujian',
            'soal_bagian_ujian',
            'bagian_ujian',
            'ujian',
            'aturan_media_materi_soal',
            'kebijakan_materi_soal',
            'media_materi_soal',
            'soal',
            'bagian_soal',
            'materi_soal',
            'bank_soal',
            'profil_mahasiswa',
            'program_studi',
            'fakultas',
            'keterampilan',
            'jenis_tes',
            'pengaturan',
            'model_has_permissions',
            'model_has_roles',
            'role_has_permissions',
            'permissions',
            'roles',
            'activity_log',
            'sessions',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function createApplicationSchema(bool $uuid): void
    {
        Schema::create('users', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('nomor_telepon', 20)->nullable();
            $table->string('foto')->nullable();
            $table->boolean('aktif')->default(true);
            $table->string('google_id')->nullable();
            $table->string('avatar_google')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) use ($uuid): void {
            $table->string('id')->primary();
            $uuid ? $table->uuid('user_id')->nullable()->index() : $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('permissions', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('roles', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table) use ($uuid): void {
            $this->foreignColumn($table, 'permission_id', 'permissions', $uuid, 'cascade');
            $this->morphId($table, 'model_id', $uuid);
            $table->string('model_type');
            $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
            $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
        });

        Schema::create('model_has_roles', function (Blueprint $table) use ($uuid): void {
            $this->foreignColumn($table, 'role_id', 'roles', $uuid, 'cascade');
            $this->morphId($table, 'model_id', $uuid);
            $table->string('model_type');
            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
            $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
        });

        Schema::create('role_has_permissions', function (Blueprint $table) use ($uuid): void {
            $this->foreignColumn($table, 'permission_id', 'permissions', $uuid, 'cascade');
            $this->foreignColumn($table, 'role_id', 'roles', $uuid, 'cascade');
            $table->primary(['permission_id', 'role_id'], 'role_has_permissions_permission_id_role_id_primary');
        });

        Schema::create('jenis_tes', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $table->string('nama', 100)->unique();
            $table->integer('batas_pelanggaran')->default(3);
            $table->text('deskripsi')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('fakultas', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $table->string('nama', 100)->unique();
            $table->string('kode', 20)->unique();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('keterampilan', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $table->string('kode', 50)->unique();
            $table->string('nama', 100);
            $table->text('deskripsi')->nullable();
            $table->integer('urutan')->default(1);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('program_studi', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $this->foreignColumn($table, 'fakultas_id', 'fakultas', $uuid, 'cascade');
            $table->string('nama', 100)->unique();
            $table->string('kode', 20)->unique();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('profil_mahasiswa', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $this->foreignColumn($table, 'pengguna_id', 'users', $uuid, 'cascade');
            $table->unique('pengguna_id');
            $table->string('nim', 20)->nullable()->index();
            $this->foreignColumn($table, 'fakultas_id', 'fakultas', $uuid, 'restrict');
            $this->foreignColumn($table, 'program_studi_id', 'program_studi', $uuid, 'restrict');
            $table->integer('tahun_angkatan')->nullable();
            $table->string('foto_identitas')->nullable();
            $table->boolean('terverifikasi')->default(false);
            $table->timestamp('diverifikasi_pada')->nullable();
            $this->foreignColumn($table, 'verifikator_id', 'users', $uuid, 'null', true);
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_soal', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $this->foreignColumn($table, 'jenis_tes_id', 'jenis_tes', $uuid, 'restrict');
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('materi_soal', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $this->foreignColumn($table, 'bank_soal_id', 'bank_soal', $uuid, 'cascade');
            $table->string('judul');
            $table->longText('isi_teks')->nullable();
            $table->timestamps();
        });

        Schema::create('bagian_soal', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $this->foreignColumn($table, 'bank_soal_id', 'bank_soal', $uuid, 'cascade');
            $this->foreignColumn($table, 'keterampilan_id', 'keterampilan', $uuid, 'restrict');
            $table->string('nama', 100);
            $table->integer('urutan')->default(1);
            $table->text('petunjuk')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->unique(['bank_soal_id', 'keterampilan_id', 'nama']);
        });

        Schema::create('soal', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $this->foreignColumn($table, 'bank_soal_id', 'bank_soal', $uuid, 'cascade');
            $this->foreignColumn($table, 'materi_soal_id', 'materi_soal', $uuid, 'null', true);
            $this->foreignColumn($table, 'keterampilan_id', 'keterampilan', $uuid, 'restrict');
            $this->foreignColumn($table, 'bagian_soal_id', 'bagian_soal', $uuid, 'restrict', true);
            $table->text('teks_soal');
            $table->text('pilihan_a');
            $table->text('pilihan_b');
            $table->text('pilihan_c');
            $table->text('pilihan_d');
            $table->char('kunci_jawaban', 1);
            $table->integer('urutan');
            $table->enum('status', ['draft', 'approved', 'rejected'])->default('draft');
            $this->foreignColumn($table, 'pembuat_id', 'users', $uuid, 'null', true);
            $this->foreignColumn($table, 'pengubah_id', 'users', $uuid, 'null', true);
            $this->foreignColumn($table, 'peninjau_id', 'users', $uuid, 'null', true);
            $table->timestamp('ditinjau_pada')->nullable();
            $table->text('catatan_tinjauan')->nullable();
            $table->timestamps();
            $table->index('bank_soal_id');
            $table->index('keterampilan_id');
            $table->index('status');
            $table->index('bagian_soal_id');
            $table->index('materi_soal_id');
        });

        Schema::create('kebijakan_materi_soal', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $this->foreignColumn($table, 'bank_soal_id', 'bank_soal', $uuid, 'cascade');
            $this->foreignColumn($table, 'keterampilan_id', 'keterampilan', $uuid, 'restrict');
            $table->string('ketentuan_materi', 20)->default('optional');
            $table->timestamps();
            $table->unique(['bank_soal_id', 'keterampilan_id'], 'kebijakan_materi_soal_bank_skill_unique');
        });

        Schema::create('aturan_media_materi_soal', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $this->foreignColumn($table, 'kebijakan_materi_soal_id', 'kebijakan_materi_soal', $uuid, 'cascade');
            $table->string('jenis_media', 20);
            $table->string('ketentuan', 20);
            $table->timestamps();
            $table->unique(['kebijakan_materi_soal_id', 'jenis_media'], 'aturan_media_materi_soal_policy_type_unique');
        });

        Schema::create('media_materi_soal', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $this->foreignColumn($table, 'materi_soal_id', 'materi_soal', $uuid, 'cascade');
            $table->string('jenis_media', 20);
            $table->string('lokasi_berkas', 255);
            $table->string('nama_asli', 255)->nullable();
            $table->string('tipe_mime', 100)->nullable();
            $table->unsignedBigInteger('ukuran_bita')->nullable();
            $table->unsignedInteger('durasi_detik')->nullable();
            $table->integer('urutan')->default(0);
            $table->timestamps();
            $table->index(['materi_soal_id', 'jenis_media']);
        });

        Schema::create('ujian', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $this->foreignColumn($table, 'jenis_tes_id', 'jenis_tes', $uuid, 'restrict');
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->enum('mode', ['tryout', 'official'])->default('tryout');
            $table->integer('durasi_menit')->default(160);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('bagian_ujian', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $this->foreignColumn($table, 'ujian_id', 'ujian', $uuid, 'cascade');
            $this->foreignColumn($table, 'bank_soal_id', 'bank_soal', $uuid, 'restrict');
            $this->foreignColumn($table, 'keterampilan_id', 'keterampilan', $uuid, 'restrict');
            $table->string('judul');
            $table->integer('urutan');
            $table->integer('jumlah_soal')->default(0);
            $table->timestamps();
            $table->index('keterampilan_id');
        });

        Schema::create('soal_bagian_ujian', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $this->foreignColumn($table, 'bagian_ujian_id', 'bagian_ujian', $uuid, 'cascade');
            $this->foreignColumn($table, 'soal_id', 'soal', $uuid, 'cascade');
            $table->integer('urutan')->default(0);
            $table->unique(['bagian_ujian_id', 'soal_id']);
        });

        Schema::create('bagian_soal_ujian', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $this->foreignColumn($table, 'bagian_ujian_id', 'bagian_ujian', $uuid, 'cascade');
            $this->foreignColumn($table, 'bagian_soal_id', 'bagian_soal', $uuid, 'restrict');
            $table->integer('urutan')->default(0);
            $table->unique(['bagian_ujian_id', 'bagian_soal_id']);
        });

        Schema::create('periode_ujian', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $this->foreignColumn($table, 'ujian_id', 'ujian', $uuid, 'cascade');
            $table->string('judul');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('slot_ujian', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $this->foreignColumn($table, 'periode_ujian_id', 'periode_ujian', $uuid, 'cascade');
            $table->date('tanggal');
            $table->time('waktu_mulai');
            $table->time('waktu_selesai');
            $table->integer('toleransi_keterlambatan_menit')->default(15);
            $table->integer('kapasitas_peserta')->default(30);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->index(['periode_ujian_id', 'tanggal']);
        });

        Schema::create('sesi_ujian', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $this->foreignColumn($table, 'slot_ujian_id', 'slot_ujian', $uuid, 'restrict');
            $this->foreignColumn($table, 'pengguna_id', 'users', $uuid, 'cascade');
            $table->enum('status', ['pending', 'in_progress', 'submitted', 'terminated'])->default('pending');
            $table->dateTime('dimulai_pada')->nullable();
            $table->dateTime('diserahkan_pada')->nullable();
            $table->dateTime('dihentikan_pada')->nullable();
            $table->string('alasan_penghentian')->nullable();
            $this->foreignColumn($table, 'bagian_aktif_id', 'bagian_ujian', $uuid, 'null', true);
            $table->dateTime('keaktifan_terakhir_pada')->nullable();
            $table->enum('status_tinjauan', ['sah', 'ujian_ulang', 'dibatalkan'])->nullable();
            $table->text('catatan_tinjauan')->nullable();
            $this->foreignColumn($table, 'peninjau_id', 'users', $uuid, 'null', true);
            $table->dateTime('ditinjau_pada')->nullable();
            $table->decimal('skor_membaca', 4, 1)->nullable();
            $table->decimal('skor_menyimak', 4, 1)->nullable();
            $table->decimal('skor_berbicara', 4, 1)->nullable();
            $table->decimal('skor_menulis', 4, 1)->nullable();
            $table->decimal('skor_total', 4, 1)->nullable();
            $table->boolean('ditandai')->default(false);
            $table->string('alasan_penandaan')->nullable();
            $table->integer('jumlah_pelanggaran')->default(0);
            $table->string('jenis_perangkat', 20)->nullable();
            $table->text('agen_pengguna_perangkat')->nullable();
            $table->string('lokasi_swafoto')->nullable();
            $table->timestamp('swafoto_diambil_pada')->nullable();
            $table->timestamps();
            $table->unique(['slot_ujian_id', 'pengguna_id']);
            $table->index('pengguna_id');
            $table->index('status');
        });

        Schema::create('aturan_penilaian', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $this->foreignColumn($table, 'jenis_tes_id', 'jenis_tes', $uuid, 'cascade');
            $table->string('kode_keterampilan', 50);
            $table->json('tabel_konversi');
            $table->integer('skor_mentah_maksimum');
            $table->integer('skor_skala_maksimum');
            $table->timestamps();
            $table->unique(['jenis_tes_id', 'kode_keterampilan']);
        });

        Schema::create('interpretasi_skor', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $this->foreignColumn($table, 'jenis_tes_id', 'jenis_tes', $uuid, 'cascade');
            $table->integer('skor_minimum');
            $table->integer('skor_maksimum');
            $table->string('tingkat_cefr', 10);
            $table->string('label_tingkat', 50);
            $table->boolean('lulus')->default(false);
            $table->text('deskripsi')->nullable();
            $table->timestamps();
            $table->unique(['jenis_tes_id', 'skor_minimum']);
        });

        Schema::create('jawaban', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $this->foreignColumn($table, 'sesi_ujian_id', 'sesi_ujian', $uuid, 'cascade');
            $this->foreignColumn($table, 'soal_id', 'soal', $uuid, 'cascade');
            $table->text('teks_jawaban')->nullable();
            $table->boolean('benar')->nullable();
            $table->timestamps();
            $table->unique(['sesi_ujian_id', 'soal_id']);
            $table->index('soal_id');
        });

        Schema::create('catatan_pelanggaran', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $this->foreignColumn($table, 'sesi_ujian_id', 'sesi_ujian', $uuid, 'cascade');
            $table->string('jenis', 50);
            $table->enum('tingkat', ['warning', 'minor', 'major', 'critical'])->default('minor');
            $table->text('deskripsi')->nullable();
            $table->integer('jumlah_pelanggaran');
            $table->timestamps();
            $table->index(['sesi_ujian_id', 'created_at']);
        });

        Schema::create('sertifikat', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $this->foreignColumn($table, 'sesi_ujian_id', 'sesi_ujian', $uuid, 'restrict');
            $table->unique('sesi_ujian_id');
            $table->string('nomor_sertifikat', 50)->unique();
            $table->dateTime('diterbitkan_pada');
            $table->date('berlaku_hingga');
            $table->timestamps();
        });

        Schema::create('pengaturan', function (Blueprint $table) use ($uuid): void {
            $this->id($table, $uuid);
            $table->string('kunci')->unique();
            $table->text('nilai')->nullable();
            $table->string('kelompok')->default('general');
            $table->timestamps();
        });

        Schema::create('activity_log', function (Blueprint $table) use ($uuid): void {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            $this->morphColumns($table, 'subject', $uuid);
            $this->morphColumns($table, 'causer', $uuid);
            $table->string('event')->nullable();
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
            $table->index('log_name');
        });
    }

    private function id(Blueprint $table, bool $uuid): void
    {
        if ($uuid) {
            $table->uuid('id')->primary();

            return;
        }

        $table->id();
    }

    private function morphId(Blueprint $table, string $column, bool $uuid, bool $nullable = false): void
    {
        if ($uuid) {
            $columnDefinition = $table->uuid($column);

            if ($nullable) {
                $columnDefinition->nullable();
            }

            return;
        }

        $columnDefinition = $table->unsignedBigInteger($column);

        if ($nullable) {
            $columnDefinition->nullable();
        }
    }

    private function morphColumns(Blueprint $table, string $name, bool $uuid): void
    {
        $table->string("{$name}_type")->nullable();
        $this->morphId($table, "{$name}_id", $uuid, true);
        $table->index(["{$name}_id", "{$name}_type"]);
    }

    private function foreignColumn(
        Blueprint $table,
        string $column,
        string $target,
        bool $uuid,
        string $onDelete = 'cascade',
        bool $nullable = false,
    ): mixed {
        $foreign = $uuid ? $table->foreignUuid($column) : $table->foreignId($column);

        if ($nullable) {
            $foreign->nullable();
        }

        $constraint = $foreign->constrained($target);

        return match ($onDelete) {
            'cascade' => $constraint->cascadeOnDelete(),
            'null' => $constraint->nullOnDelete(),
            'restrict' => $constraint->restrictOnDelete(),
            default => $constraint,
        };
    }
};
