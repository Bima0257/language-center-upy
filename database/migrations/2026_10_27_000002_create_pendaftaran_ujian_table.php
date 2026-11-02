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
        Schema::create('pendaftaran_ujian', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('slot_ujian_id')
                ->constrained('slot_ujian')
                ->restrictOnDelete();
            $table->foreignUuid('pengguna_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->string('lokasi_bukti_pembayaran', 255)->nullable();
            $table->string('nama_asli_bukti_pembayaran', 255)->nullable();
            $table->string('tipe_mime_bukti_pembayaran', 100)->nullable();
            $table->unsignedBigInteger('ukuran_bukti_pembayaran')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->foreignUuid('diproses_oleh_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('diproses_pada')->nullable();
            $table->timestamps();

            $table->unique(['slot_ujian_id', 'pengguna_id']);
            $table->index('status');
            $table->index(['slot_ujian_id', 'status']);
            $table->index(['pengguna_id', 'status']);
        });

        $this->backfillExistingSessions();
    }

    public function down(): void
    {
        Schema::dropIfExists('pendaftaran_ujian');
    }

    private function backfillExistingSessions(): void
    {
        DB::table('sesi_ujian')
            ->select(['slot_ujian_id', 'pengguna_id', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->chunkById(500, function ($sessions): void {
                $rows = $sessions->map(fn ($session): array => [
                    'id' => (string) Str::uuid(),
                    'slot_ujian_id' => $session->slot_ujian_id,
                    'pengguna_id' => $session->pengguna_id,
                    'status' => 'approved',
                    'catatan_admin' => 'Dibuat otomatis saat migrasi pendaftaran ujian.',
                    'diproses_pada' => $session->created_at,
                    'created_at' => $session->created_at,
                    'updated_at' => $session->updated_at,
                ])->all();

                if ($rows !== []) {
                    DB::table('pendaftaran_ujian')->insertOrIgnore($rows);
                }
            }, 'id');
    }
};
