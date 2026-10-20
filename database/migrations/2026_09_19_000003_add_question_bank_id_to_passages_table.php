<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('passages', function (Blueprint $table) {
            $table->foreignId('question_bank_id')->nullable()->constrained('question_banks')->restrictOnDelete();
        });

        $passageBanks = DB::table('questions')
            ->whereNotNull('passage_id')
            ->select('passage_id', 'question_bank_id')
            ->distinct()
            ->get()
            ->groupBy('passage_id');

        foreach ($passageBanks as $passageId => $rows) {
            $bankIds = $rows->pluck('question_bank_id')->unique()->values();
            $ownerBankId = $bankIds->first();

            DB::table('passages')->where('id', $passageId)->update(['question_bank_id' => $ownerBankId]);

            // Passage dipakai lebih dari satu bank: pertahankan bank pertama sebagai pemilik,
            // duplikasi passage untuk bank lainnya lalu arahkan soalnya ke duplikat tersebut.
            foreach ($bankIds->skip(1) as $extraBankId) {
                $passage = DB::table('passages')->where('id', $passageId)->first();

                $newPassageId = DB::table('passages')->insertGetId([
                    'question_bank_id' => $extraBankId,
                    'title' => $passage->title,
                    'content_text' => $passage->content_text,
                    'created_at' => $passage->created_at,
                    'updated_at' => now(),
                ]);

                DB::table('questions')
                    ->where('passage_id', $passageId)
                    ->where('question_bank_id', $extraBankId)
                    ->update(['passage_id' => $newPassageId]);
            }
        }

        // Passage tanpa soal tidak memiliki pemilik bank — tidak dapat dimigrasikan.
        DB::table('passages')->whereNull('question_bank_id')->delete();

        Schema::table('passages', function (Blueprint $table) {
            $table->dropForeign(['question_bank_id']);
        });

        Schema::table('passages', function (Blueprint $table) {
            $table->foreignId('question_bank_id')->nullable(false)->change();
        });

        Schema::table('passages', function (Blueprint $table) {
            $table->foreign('question_bank_id')->references('id')->on('question_banks')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('passages', function (Blueprint $table) {
            $table->dropForeign(['question_bank_id']);
            $table->dropColumn('question_bank_id');
        });
    }
};
