<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('passages', function (Blueprint $table) {
            $table->enum('type', ['text', 'audio', 'image'])->default('text')->change();
        });
    }

    public function down(): void
    {
        Schema::table('passages', function (Blueprint $table) {
            $table->enum('type', ['text', 'audio', 'image'])->change();
        });
    }
};
