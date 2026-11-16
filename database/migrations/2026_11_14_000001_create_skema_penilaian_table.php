<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skema_penilaian', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('program_tes_id')
                ->constrained('program_tes')
                ->restrictOnDelete();
            $table->unsignedInteger('versi');
            $table->string('nama');
            $table->enum('status', ['draft', 'aktif', 'arsip'])->default('draft');
            $table->enum('metode_total', ['jumlah'])->default('jumlah');
            $table->decimal('skala_total_minimum', 6, 1)->nullable();
            $table->decimal('skala_total_maksimum', 6, 1)->nullable();
            $table->timestamp('diaktifkan_pada')->nullable();
            $table->foreignUuid('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('diaktifkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['program_tes_id', 'versi']);
            $table->index(['program_tes_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skema_penilaian');
    }
};
