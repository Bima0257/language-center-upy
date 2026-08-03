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
            // questions.status: 3 nilai
            DB::statement('ALTER TABLE questions DROP CONSTRAINT IF EXISTS questions_status_check');
            DB::statement("ALTER TABLE questions ADD CONSTRAINT questions_status_check CHECK (status IN ('draft','approved','rejected'))");

            // exam_sessions.status: 4 nilai
            DB::statement("ALTER TABLE exam_sessions MODIFY status ENUM('pending','in_progress','submitted','terminated') NOT NULL DEFAULT 'pending'");
        }

        // skill_id → NOT NULL (MySQL: MODIFY; SQLite: rebuild otomatis oleh Laravel)
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('skill_id')->nullable(false)->change();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        Schema::disableForeignKeyConstraints();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE questions DROP CONSTRAINT IF EXISTS questions_status_check');
            DB::statement("ALTER TABLE questions ADD CONSTRAINT questions_status_check CHECK (status IN ('draft','submitted','approved','rejected','archived'))");
            DB::statement("ALTER TABLE exam_sessions MODIFY status ENUM('pending','in_progress','submitted','terminated','reviewed') NOT NULL DEFAULT 'pending'");
        }

        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('skill_id')->nullable()->change();
        });

        Schema::enableForeignKeyConstraints();
    }
};
