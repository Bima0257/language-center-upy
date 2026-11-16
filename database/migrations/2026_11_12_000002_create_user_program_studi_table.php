<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_program_studi', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('program_studi_id')->constrained('program_studi')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'program_studi_id']);
        });

        DB::table('profil_mahasiswa')
            ->select(['pengguna_id', 'program_studi_id'])
            ->whereNotNull('program_studi_id')
            ->orderBy('id')
            ->chunk(100, function ($profiles): void {
                foreach ($profiles as $profile) {
                    DB::table('user_program_studi')->insertOrIgnore([
                        'id' => (string) Str::uuid(),
                        'user_id' => $profile->pengguna_id,
                        'program_studi_id' => $profile->program_studi_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_program_studi');
    }
};
