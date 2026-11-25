<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ujian', function (Blueprint $table): void {
            $table->enum('lingkup', ['umum', 'prodi'])
                ->default('umum')
                ->after('durasi_menit');
        });

        Schema::create('ujian_program_studi', function (Blueprint $table): void {
            $table->foreignUuid('ujian_id')
                ->constrained('ujian')
                ->cascadeOnDelete();
            $table->foreignUuid('program_studi_id')
                ->constrained('program_studi')
                ->restrictOnDelete();
            $table->timestamps();

            $table->primary(['ujian_id', 'program_studi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ujian_program_studi');

        Schema::table('ujian', function (Blueprint $table): void {
            $table->dropColumn('lingkup');
        });
    }
};
