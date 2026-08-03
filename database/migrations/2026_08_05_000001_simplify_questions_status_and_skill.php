<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        $prefix = DB::getTablePrefix();

        Schema::disableForeignKeyConstraints();

        // ===== 1. questions.status: submitted/archived → draft =====
        DB::statement("UPDATE {$prefix}questions SET status = 'draft' WHERE status IN ('submitted', 'archived')");

        // ===== 2. questions.skill_id: guard nulls before NOT NULL =====
        DB::statement("UPDATE {$prefix}questions SET skill_id = (SELECT MIN(id) FROM {$prefix}skills) WHERE skill_id IS NULL");

        // ===== 3. exam_sessions.status: reviewed → submitted =====
        DB::statement("UPDATE {$prefix}exam_sessions SET status = 'submitted' WHERE status = 'reviewed'");

        if ($driver === 'mysql') {
            // questions.status: 3 nilai (ENUM murni dari migration create)
            DB::statement("ALTER TABLE questions MODIFY status ENUM('draft','approved','rejected') NOT NULL DEFAULT 'draft'");

            // exam_sessions.status: 4 nilai
            DB::statement("ALTER TABLE exam_sessions MODIFY status ENUM('pending','in_progress','submitted','terminated') NOT NULL DEFAULT 'pending'");
        }

        // ===== 4. questions.skill_id → NOT NULL =====
        // Langkah penting: FK lama punya ON DELETE SET NULL yang kontradiktif dengan NOT NULL (MySQL error 1830)
        // 4a. Drop FK lama
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE {$prefix}questions DROP FOREIGN KEY questions_skill_id_foreign");
        } else {
            Schema::table('questions', function (Blueprint $table) {
                $table->dropForeign(['skill_id']);
            });
        }

        // 4b. Ubah kolom → NOT NULL (MySQL: MODIFY; SQLite: rebuild otomatis oleh Laravel)
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('skill_id')->nullable(false)->change();
        });

        // 4c. Tambah FK baru → RESTRICT (skill tidak bisa dihapus selama masih dipakai soal)
        Schema::table('questions', function (Blueprint $table) {
            $table->foreign('skill_id')->references('id')->on('skills');
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        $prefix = DB::getTablePrefix();

        Schema::disableForeignKeyConstraints();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE questions MODIFY status ENUM('draft','submitted','approved','rejected','archived') NOT NULL DEFAULT 'draft'");
            DB::statement("ALTER TABLE exam_sessions MODIFY status ENUM('pending','in_progress','submitted','terminated','reviewed') NOT NULL DEFAULT 'pending'");
        }

        // ===== skill_id: balikan (RESTRICT → nullable → SET NULL) =====
        // 1. Drop FK RESTRICT
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE {$prefix}questions DROP FOREIGN KEY questions_skill_id_foreign");
        } else {
            Schema::table('questions', function (Blueprint $table) {
                $table->dropForeign(['skill_id']);
            });
        }

        // 2. Ubah kolom → nullable
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('skill_id')->nullable()->change();
        });

        // 3. Tambah FK SET NULL (seperti semula)
        Schema::table('questions', function (Blueprint $table) {
            $table->foreign('skill_id')->references('id')->on('skills')->nullOnDelete();
        });

        Schema::enableForeignKeyConstraints();
    }
};
