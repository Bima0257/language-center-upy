<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_form_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_form_id')->constrained('test_forms')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->integer('order')->default(0);

            $table->unique(['test_form_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_form_questions');
    }
};
