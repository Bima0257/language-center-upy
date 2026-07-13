<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('violation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->enum('severity', ['warning', 'minor', 'major', 'critical'])->default('minor');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->integer('strike_count');
            $table->string('screenshot_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('violation_logs');
    }
};
