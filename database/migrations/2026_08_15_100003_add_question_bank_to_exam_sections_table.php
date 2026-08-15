<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sections', function (Blueprint $table) {
            $table->foreignId('question_bank_id')->nullable()->after('exam_id');
        });

        // Backfill: section existing diisi bank dari soal pertama yang terpasang
        DB::table('exam_sections')->orderBy('id')->chunkById(100, function ($sections) {
            foreach ($sections as $section) {
                $bankId = DB::table('exam_section_questions')
                    ->join('questions', 'questions.id', '=', 'exam_section_questions.question_id')
                    ->where('exam_section_questions.exam_section_id', $section->id)
                    ->value('questions.question_bank_id');

                if ($bankId !== null) {
                    DB::table('exam_sections')->where('id', $section->id)->update(['question_bank_id' => $bankId]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_sections', function (Blueprint $table) {
            $table->dropColumn('question_bank_id');
        });
    }
};
