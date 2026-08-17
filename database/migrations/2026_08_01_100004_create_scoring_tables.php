<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scoring_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_type_id')->constrained()->cascadeOnDelete();
            $table->string('section_skill', 50);
            $table->json('conversion_table');
            $table->integer('max_raw');
            $table->integer('max_scaled');
            $table->timestamps();
            $table->unique(['exam_type_id', 'section_skill']);
        });

        Schema::create('score_interpretations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_type_id')->constrained()->cascadeOnDelete();
            $table->integer('min_score');
            $table->integer('max_score');
            $table->string('cefr_level', 10);
            $table->string('level_label', 50);
            $table->boolean('is_passing')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['exam_type_id', 'min_score']);
        });

        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->text('answer_text')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->timestamps();
            $table->unique(['exam_session_id', 'question_id']);
            $table->index('question_id');
        });

        Schema::create('violation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->enum('severity', ['warning', 'minor', 'major', 'critical'])->default('minor');
            $table->text('description')->nullable();
            $table->integer('strike_count');
            $table->timestamps();
            $table->index(['exam_session_id', 'created_at']);
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->unique()->constrained()->restrictOnDelete();
            $table->string('certificate_number', 50)->unique();
            $table->dateTime('issued_at');
            $table->date('valid_until');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('violation_logs');
        Schema::dropIfExists('answers');
        Schema::dropIfExists('score_interpretations');
        Schema::dropIfExists('scoring_rules');
    }
};
