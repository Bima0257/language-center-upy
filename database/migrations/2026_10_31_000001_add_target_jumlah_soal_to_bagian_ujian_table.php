<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bagian_ujian', function (Blueprint $table): void {
            $table->unsignedInteger('target_jumlah_soal')->nullable()->after('jumlah_soal');
        });

        DB::table('bagian_ujian')
            ->where('jumlah_soal', '>', 0)
            ->update(['target_jumlah_soal' => DB::raw('jumlah_soal')]);
    }

    public function down(): void
    {
        Schema::table('bagian_ujian', function (Blueprint $table): void {
            $table->dropColumn('target_jumlah_soal');
        });
    }
};
