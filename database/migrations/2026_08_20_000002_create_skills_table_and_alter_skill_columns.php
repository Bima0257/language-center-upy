<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Buat tabel skills
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->integer('order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Seed skill reading & listening
        DB::table('skills')->insert([
            ['code' => 'reading', 'name' => 'Reading', 'description' => null, 'order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'listening', 'name' => 'Listening', 'description' => null, 'order' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 3. Tambah skill_id ke skill_parts
        Schema::table('skill_parts', function (Blueprint $table) {
            $table->foreignId('skill_id')->nullable()->after('question_bank_id')->constrained('skills')->restrictOnDelete();
        });

        // 4. Update skill_parts: set skill_id berdasarkan skill code
        $readingId = DB::table('skills')->where('code', 'reading')->value('id');
        $listeningId = DB::table('skills')->where('code', 'listening')->value('id');

        DB::table('skill_parts')->where('skill', 'reading')->update(['skill_id' => $readingId]);
        DB::table('skill_parts')->where('skill', 'listening')->update(['skill_id' => $listeningId]);

        // 5. Buat skill_id non-nullable
        Schema::table('skill_parts', function (Blueprint $table) {
            $table->foreignId('skill_id')->nullable(false)->change();
        });

        // 6. Hapus kolom lama skill dari skill_parts
        // MySQL: unique index (question_bank_id, skill, name) dipakai FK question_bank_id → drop FK dulu
        Schema::table('skill_parts', function (Blueprint $table) {
            $table->dropForeign(['question_bank_id']);
        });
        Schema::table('skill_parts', function (Blueprint $table) {
            $table->dropUnique('skill_parts_question_bank_id_skill_name_unique');
            $table->dropIndex('skill_parts_skill_index');
            $table->dropColumn('skill');
        });

        // 7. Update unique constraint skill_parts + re-add FK question_bank_id
        Schema::table('skill_parts', function (Blueprint $table) {
            $table->unique(['question_bank_id', 'skill_id', 'name']);
            $table->foreign('question_bank_id')->references('id')->on('question_banks')->cascadeOnDelete();
        });

        // 8. Tambah skill_id ke questions
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('skill_id')->nullable()->after('question_bank_id')->constrained('skills')->restrictOnDelete();
        });

        // 9. Update questions: set skill_id
        DB::table('questions')->where('skill', 'reading')->update(['skill_id' => $readingId]);
        DB::table('questions')->where('skill', 'listening')->update(['skill_id' => $listeningId]);

        // 10. Buat skill_id non-nullable di questions
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('skill_id')->nullable(false)->change();
        });

        // 11. Hapus kolom lama skill dari questions
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['skill']);
            $table->dropColumn('skill');
        });

        // 12. Tambah skill_id ke exam_sections
        Schema::table('exam_sections', function (Blueprint $table) {
            $table->foreignId('skill_id')->nullable()->after('question_bank_id')->constrained('skills')->restrictOnDelete();
        });

        // 13. Update exam_sections: set skill_id
        DB::table('exam_sections')->where('skill', 'reading')->update(['skill_id' => $readingId]);
        DB::table('exam_sections')->where('skill', 'listening')->update(['skill_id' => $listeningId]);

        // 14. Buat skill_id non-nullable di exam_sections
        Schema::table('exam_sections', function (Blueprint $table) {
            $table->foreignId('skill_id')->nullable(false)->change();
        });

        // 15. Hapus kolom lama skill dari exam_sections
        Schema::table('exam_sections', function (Blueprint $table) {
            $table->dropIndex(['skill']);
            $table->dropColumn('skill');
        });
    }

    public function down(): void
    {
        // Reverse: tambah kolom skill string kembali
        Schema::table('exam_sections', function (Blueprint $table) {
            $table->string('skill', 50)->after('question_bank_id');
            $table->index('skill');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->string('skill', 50)->after('question_bank_id');
            $table->index('skill');
        });

        Schema::table('skill_parts', function (Blueprint $table) {
            $table->string('skill', 50)->after('question_bank_id');
            $table->index('skill');
        });

        // Restore skill values from skills table
        $skills = DB::table('skills')->pluck('code', 'id');
        foreach ($skills as $id => $code) {
            DB::table('skill_parts')->where('skill_id', $id)->update(['skill' => $code]);
            DB::table('questions')->where('skill_id', $id)->update(['skill' => $code]);
            DB::table('exam_sections')->where('skill_id', $id)->update(['skill' => $code]);
        }

        // Drop foreign keys and skill_id columns
        Schema::table('exam_sections', function (Blueprint $table) {
            $table->dropForeign(['skill_id']);
            $table->dropColumn('skill_id');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['skill_id']);
            $table->dropColumn('skill_id');
        });

        Schema::table('skill_parts', function (Blueprint $table) {
            $table->dropForeign(['question_bank_id']);
            $table->dropUnique(['question_bank_id', 'skill_id', 'name']);
            $table->dropForeign(['skill_id']);
            $table->dropColumn('skill_id');
            $table->foreign('question_bank_id')->references('id')->on('question_banks')->cascadeOnDelete();
        });

        Schema::dropIfExists('skills');
    }
};
