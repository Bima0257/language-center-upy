<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
            $table->foreignId('skill_part_id')->constrained()->cascadeOnDelete();
            $table->integer('order')->default(0);
            $table->unique(['exam_section_id', 'skill_part_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_section_parts');
        Schema::dropIfExists('exam_section_questions');
    }
};
