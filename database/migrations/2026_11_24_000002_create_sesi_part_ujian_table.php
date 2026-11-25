<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesi_part_ujian', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('sesi_ujian_id')->constrained('sesi_ujian')->cascadeOnDelete();
            $table->foreignUuid('part_ujian_id')->constrained('part_ujian')->cascadeOnDelete();
            $table->unsignedSmallInteger('durasi_menit');
            $table->dateTime('dimulai_pada');
            $table->dateTime('selesai_pada')->nullable();
            $table->string('alasan_selesai', 20)->nullable();
            $table->timestamps();

            $table->unique(['sesi_ujian_id', 'part_ujian_id']);
            $table->index(['sesi_ujian_id', 'selesai_pada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesi_part_ujian');
    }
};
