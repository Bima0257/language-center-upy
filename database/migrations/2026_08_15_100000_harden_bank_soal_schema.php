<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skill_parts', function (Blueprint $table) {
            $table->enum('skill', ['reading', 'listening'])->change();
            $table->unique(['skill', 'name']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->enum('skill', ['reading', 'listening'])->change();
        });

        Schema::table('exam_sections', function (Blueprint $table) {
            $table->enum('skill', ['reading', 'listening'])->change();
        });

        Schema::table('passages', function (Blueprint $table) {
            $table->enum('type', ['text', 'audio', 'image'])->change();
        });

        Schema::table('question_banks', function (Blueprint $table) {
            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->dropUnique('question_banks_name_unique');
        });

        Schema::table('passages', function (Blueprint $table) {
            $table->string('type', 50)->default('text')->change();
        });

        Schema::table('exam_sections', function (Blueprint $table) {
            $table->string('skill', 50)->change();
        });

        Schema::table('skill_parts', function (Blueprint $table) {
            $table->dropUnique('skill_parts_skill_name_unique');
            $table->string('skill', 50)->change();
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->string('skill', 50)->change();
        });
    }
};
