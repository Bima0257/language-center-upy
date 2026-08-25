<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->string('device_type', 20)->nullable()->after('violation_strikes');
            $table->text('device_user_agent')->nullable()->after('device_type');
            $table->string('selfie_path')->nullable()->after('device_user_agent');
            $table->timestamp('selfie_taken_at')->nullable()->after('selfie_path');
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn(['device_type', 'device_user_agent', 'selfie_path', 'selfie_taken_at']);
        });
    }
};
