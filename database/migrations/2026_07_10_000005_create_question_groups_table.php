<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('passage_id')->nullable()->constrained('passages')->nullOnDelete();
            $table->string('title')->nullable();
            $table->enum('group_type', ['passage', 'conversation', 'lecture', 'standalone', 'prompt'])->default('passage');
            $table->longText('passage_text')->nullable();
            $table->string('audio_file')->nullable();
            $table->string('image')->nullable();
            $table->string('topic')->nullable();
            $table->integer('word_count')->nullable();
            $table->integer('order');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_groups');
    }
};
