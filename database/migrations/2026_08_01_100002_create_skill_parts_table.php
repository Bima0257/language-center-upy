<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_bank_id')->constrained()->cascadeOnDelete();
            $table->enum('skill', ['reading', 'listening']);
            $table->string('name', 100);
            $table->integer('order')->default(1);
            $table->text('directions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['question_bank_id', 'skill', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_parts');
    }
};
