<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. skill_parts: skill_id -> skill (string code)
        Schema::table('skill_parts', function (Blueprint $table) {
            $table->string('skill', 50)->nullable()->after('skill_id');
            $table->index('skill');
        });

        DB::statement('UPDATE skill_parts SET skill = (SELECT skills.code FROM skills WHERE skills.id = skill_parts.skill_id)');

        Schema::table('skill_parts', function (Blueprint $table) {
            $table->string('skill', 50)->nullable(false)->change();
            $table->dropConstrainedForeignId('skill_id');
        });

        // 2. questions: skill_id -> skill; drop material_type + media per-soal (media hanya di passages)
        Schema::table('questions', function (Blueprint $table) {
            $table->string('skill', 50)->nullable()->after('type');
            $table->index('skill');
        });

        DB::statement('UPDATE questions SET skill = (SELECT skills.code FROM skills WHERE skills.id = questions.skill_id)');

        Schema::table('questions', function (Blueprint $table) {
            $table->string('skill', 50)->nullable(false)->change();
            $table->dropConstrainedForeignId('skill_id');
            $table->dropColumn(['material_type', 'audio_url', 'image_url']);
        });

        // 3. exam_sections: skill_id -> skill
        Schema::table('exam_sections', function (Blueprint $table) {
            $table->string('skill', 50)->nullable()->after('exam_id');
            $table->index('skill');
        });

        DB::statement('UPDATE exam_sections SET skill = (SELECT skills.code FROM skills WHERE skills.id = exam_sections.skill_id)');

        Schema::table('exam_sections', function (Blueprint $table) {
            $table->string('skill', 50)->nullable(false)->change();
            $table->dropConstrainedForeignId('skill_id');
        });

        // 4. skills tidak lagi master data
        Schema::dropIfExists('skills');
    }

    public function down(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 100)->unique();
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::statement("INSERT INTO skills (name, code, is_active, created_at, updated_at)
            VALUES ('Reading', 'reading', 1, now(), now()), ('Listening', 'listening', 1, now(), now())");

        Schema::table('skill_parts', function (Blueprint $table) {
            $table->foreignId('skill_id')->nullable()->after('id');
        });

        DB::statement('UPDATE skill_parts SET skill_id = (SELECT skills.id FROM skills WHERE skills.code = skill_parts.skill)');

        Schema::table('skill_parts', function (Blueprint $table) {
            $table->foreignId('skill_id')->nullable(false)->change();
            $table->dropColumn(['skill']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('skill_id')->nullable()->after('id');
            $table->string('material_type', 20)->default('text');
            $table->string('audio_url')->nullable();
            $table->string('image_url')->nullable();
        });

        DB::statement('UPDATE questions SET skill_id = (SELECT skills.id FROM skills WHERE skills.code = questions.skill)');

        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('skill_id')->nullable(false)->change();
            $table->dropColumn(['skill']);
        });

        Schema::table('exam_sections', function (Blueprint $table) {
            $table->foreignId('skill_id')->nullable()->after('id');
        });

        DB::statement('UPDATE exam_sections SET skill_id = (SELECT skills.id FROM skills WHERE skills.code = exam_sections.skill)');

        Schema::table('exam_sections', function (Blueprint $table) {
            $table->foreignId('skill_id')->nullable(false)->change();
            $table->dropColumn(['skill']);
        });
    }
};
