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

        // 1. Add new columns (supported by all drivers)
        Schema::table('questions', function (Blueprint $table) {
            $table->string('skill', 50)->after('type');
            $table->foreignId('passage_id')->nullable()->after('skill')->constrained('passages')->nullOnDelete();
            $table->string('audio_file')->nullable()->after('passage_id');
            $table->foreignId('reviewed_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_note')->nullable()->after('reviewed_at');
        });

        // 2. Make question_group_id nullable
        if ($driver === 'sqlite') {
            Schema::table('questions', function (Blueprint $table) {
                $table->dropForeign(['question_group_id']);
            });

            DB::statement('ALTER TABLE questions ADD COLUMN tmp_qg_id INTEGER REFERENCES question_groups(id) ON DELETE SET NULL');

            DB::statement('UPDATE questions SET tmp_qg_id = question_group_id');

            DB::statement('ALTER TABLE questions DROP COLUMN question_group_id');

            DB::statement('ALTER TABLE questions RENAME COLUMN tmp_qg_id TO question_group_id');

            Schema::table('questions', function (Blueprint $table) {
                $table->index('question_group_id');
            });
        } else {
            Schema::table('questions', function (Blueprint $table) {
                $table->dropForeign(['question_group_id']);
            });

            DB::statement('ALTER TABLE questions MODIFY question_group_id BIGINT UNSIGNED NULL');

            Schema::table('questions', function (Blueprint $table) {
                $table->foreign('question_group_id')
                    ->references('id')
                    ->on('question_groups')
                    ->nullOnDelete();
            });
        }

        // 3. Update status enum for review workflow (MySQL/Postgres only; SQLite stores as VARCHAR)
        if ($driver !== 'sqlite') {
            DB::statement("ALTER TABLE questions MODIFY status VARCHAR(20) NOT NULL DEFAULT 'draft'");
            DB::statement("ALTER TABLE questions ADD CONSTRAINT questions_status_check CHECK (status IN ('draft','submitted','approved','rejected','archived'))");
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['passage_id']);
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn([
                'skill',
                'passage_id',
                'audio_file',
                'reviewed_by',
                'reviewed_at',
                'review_note',
            ]);
        });

        // Revert question_group_id to NOT NULL
        if ($driver === 'sqlite') {
            Schema::table('questions', function (Blueprint $table) {
                $table->dropForeign(['question_group_id']);
            });

            DB::statement('ALTER TABLE questions ADD COLUMN tmp_qg_id INTEGER NOT NULL REFERENCES question_groups(id) ON DELETE CASCADE');

            DB::statement('UPDATE questions SET tmp_qg_id = question_group_id WHERE question_group_id IS NOT NULL');

            DB::statement('ALTER TABLE questions DROP COLUMN question_group_id');

            DB::statement('ALTER TABLE questions RENAME COLUMN tmp_qg_id TO question_group_id');

            Schema::table('questions', function (Blueprint $table) {
                $table->index('question_group_id');
            });
        } else {
            Schema::table('questions', function (Blueprint $table) {
                $table->dropForeign(['question_group_id']);
            });

            DB::statement('ALTER TABLE questions MODIFY question_group_id BIGINT UNSIGNED NOT NULL');

            Schema::table('questions', function (Blueprint $table) {
                $table->foreign('question_group_id')
                    ->references('id')
                    ->on('question_groups')
                    ->cascadeOnDelete();
            });
        }

        if ($driver !== 'sqlite') {
            DB::statement('ALTER TABLE questions DROP CONSTRAINT IF EXISTS questions_status_check');
            DB::statement("ALTER TABLE questions MODIFY status ENUM('draft','active','archived') NOT NULL DEFAULT 'draft'");
        }
    }
};
