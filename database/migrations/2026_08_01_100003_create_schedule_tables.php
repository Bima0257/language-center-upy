<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('exam_schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_schedule_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('late_tolerance_minutes')->default(15);
            $table->integer('max_participants')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['exam_schedule_id', 'date']);
        });

        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_schedule_slot_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'in_progress', 'submitted', 'terminated'])->default('pending');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('terminated_at')->nullable();
            $table->string('termination_reason')->nullable();
            $table->foreignId('current_section_id')->nullable()->constrained('exam_sections')->nullOnDelete();
            $table->dateTime('last_heartbeat_at')->nullable();
            $table->enum('review_status', ['sah', 'ujian_ulang', 'dibatalkan'])->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->decimal('score_reading', 4, 1)->nullable();
            $table->decimal('score_listening', 4, 1)->nullable();
            $table->decimal('score_speaking', 4, 1)->nullable();
            $table->decimal('score_writing', 4, 1)->nullable();
            $table->decimal('score_total', 4, 1)->nullable();
            $table->boolean('is_flagged')->default(false);
            $table->string('flag_reason')->nullable();
            $table->integer('violation_strikes')->default(0);
            $table->timestamps();
            $table->unique(['exam_schedule_slot_id', 'user_id']);
            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_sessions');
        Schema::dropIfExists('exam_schedule_slots');
        Schema::dropIfExists('exam_schedules');
    }
};
