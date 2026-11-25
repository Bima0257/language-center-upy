<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('section', function (Blueprint $table): void {
            $table->unsignedSmallInteger('durasi_menit')->default(30)->after('urutan');
        });
    }

    public function down(): void
    {
        Schema::table('section', function (Blueprint $table): void {
            $table->dropColumn('durasi_menit');
        });
    }
};
