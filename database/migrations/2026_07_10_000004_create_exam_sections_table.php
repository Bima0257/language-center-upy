<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->string('skill', 50);
            $table->enum('type', ['reading', 'listening', 'speaking', 'writing'])->nullable();
            $table->string('title');
            $table->integer('order');
            $table->integer('duration_minutes')->nullable();
            $table->text('instructions')->nullable();
            $table->integer('total_questions')->default(0);
            $table->boolean('navigation_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_sections');
    }
};
