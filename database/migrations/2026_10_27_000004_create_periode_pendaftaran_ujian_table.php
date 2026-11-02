<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periode_pendaftaran_ujian', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('periode_ujian_id')
                ->unique()
                ->constrained('periode_ujian')
                ->cascadeOnDelete();
            $table->string('nama', 255);
            $table->dateTime('dibuka_pada');
            $table->dateTime('ditutup_pada');
            $table->boolean('aktif')->default(true);
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['aktif', 'dibuka_pada', 'ditutup_pada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periode_pendaftaran_ujian');
    }
};
