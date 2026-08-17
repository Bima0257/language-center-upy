<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_type_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('mode', ['tryout', 'official'])->default('tryout');
            $table->integer('duration_minutes')->default(160);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('exam_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_bank_id')->constrained()->restrictOnDelete();
            $table->string('skill', 50);
            $table->string('title');
            $table->integer('order');
            $table->integer('total_questions')->default(0);
            $table->timestamps();
            $table->index('skill');
        });

        Schema::create('exam_section_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->integer('order')->default(0);
            $table->unique(['exam_section_id', 'question_id']);
        });

        Schema::create('exam_section_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_part_id')->constrained()->restrictOnDelete();
            $table->integer('order')->default(0);
            $table->unique(['exam_section_id', 'skill_part_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_section_parts');
        Schema::dropIfExists('exam_section_questions');
        Schema::dropIfExists('exam_sections');
        Schema::dropIfExists('exams');
    }
};
