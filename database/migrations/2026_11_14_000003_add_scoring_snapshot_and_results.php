<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sesi_ujian', function (Blueprint $table): void {
            $table->foreignUuid('skema_penilaian_id')
                ->nullable()
                ->after('bagian_aktif_id')
                ->constrained('skema_penilaian')
                ->restrictOnDelete();
            $table->json('snapshot_penilaian')->nullable()->after('skema_penilaian_id');
            $table->decimal('skor_total_maksimum', 6, 1)->nullable()->after('skor_total');
            $table->boolean('lulus')->nullable()->after('skor_total_maksimum');
            $table->string('tingkat_cefr', 10)->nullable()->after('lulus');
            $table->string('label_tingkat', 50)->nullable()->after('tingkat_cefr');
            $table->text('deskripsi_tingkat')->nullable()->after('label_tingkat');
        });

        Schema::table('sesi_ujian', function (Blueprint $table): void {
            $table->decimal('skor_membaca', 6, 1)->nullable()->change();
            $table->decimal('skor_menyimak', 6, 1)->nullable()->change();
            $table->decimal('skor_berbicara', 6, 1)->nullable()->change();
            $table->decimal('skor_menulis', 6, 1)->nullable()->change();
            $table->decimal('skor_total', 6, 1)->nullable()->change();
        });

        Schema::create('hasil_penilaian', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('sesi_ujian_id')
                ->constrained('sesi_ujian')
                ->cascadeOnDelete();
            $table->foreignUuid('section_id')
                ->constrained('section')
                ->restrictOnDelete();
            $table->foreignUuid('skema_penilaian_id')
                ->constrained('skema_penilaian')
                ->restrictOnDelete();
            $table->integer('jumlah_benar');
            $table->integer('jumlah_soal');
            $table->decimal('skor_skala', 6, 1);
            $table->json('rincian')->nullable();
            $table->timestamps();

            $table->unique(['sesi_ujian_id', 'section_id']);
            $table->index('section_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hasil_penilaian');

        Schema::table('sesi_ujian', function (Blueprint $table): void {
            $table->decimal('skor_membaca', 4, 1)->nullable()->change();
            $table->decimal('skor_menyimak', 4, 1)->nullable()->change();
            $table->decimal('skor_berbicara', 4, 1)->nullable()->change();
            $table->decimal('skor_menulis', 4, 1)->nullable()->change();
            $table->decimal('skor_total', 4, 1)->nullable()->change();
        });

        Schema::table('sesi_ujian', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('skema_penilaian_id');
            $table->dropColumn([
                'snapshot_penilaian',
                'skor_total_maksimum',
                'lulus',
                'tingkat_cefr',
                'label_tingkat',
                'deskripsi_tingkat',
            ]);
        });
    }
};
