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
            $table->foreignId('exam_type_id')->constrained('exam_types')->cascadeOnDelete();
            $table->string('section_skill', 50);
            $table->json('conversion_table');
            $table->enum('scoring_method', ['raw_to_scaled', 'rubric', 'weighted'])->default('raw_to_scaled');
            $table->json('rubric')->nullable();
            $table->integer('max_raw');
            $table->integer('max_scaled');
            $table->timestamps();

            $table->unique(['exam_type_id', 'section_skill']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scoring_rules');
    }
};
