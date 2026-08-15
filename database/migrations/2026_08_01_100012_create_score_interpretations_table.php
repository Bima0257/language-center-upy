<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('score_interpretations');
    }
};
