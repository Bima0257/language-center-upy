<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->foreignId('exam_type_id')->nullable()->after('name')->constrained()->nullOnDelete();
        });

        $toeflId = DB::table('exam_types')->where('name', 'like', 'TOEFL%')->value('id');

        if ($toeflId) {
            DB::table('question_banks')->whereNull('exam_type_id')->update(['exam_type_id' => $toeflId]);
        }
    }

    public function down(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exam_type_id');
        });
    }
};
