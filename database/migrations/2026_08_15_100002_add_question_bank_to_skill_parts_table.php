<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skill_parts', function (Blueprint $table) {
            $table->dropUnique('skill_parts_skill_name_unique');
        });

        Schema::table('skill_parts', function (Blueprint $table) {
            $table->foreignId('question_bank_id')->nullable()->after('id');
        });

        // Klon part per bank: setiap bank yang punya soal mendapat salinan part sendiri
        DB::transaction(function () {
            $bankIds = DB::table('questions')->select('question_bank_id')->distinct()->pluck('question_bank_id');

            foreach ($bankIds as $bankId) {
                $partIdsUsedByBank = DB::table('questions')
                    ->where('question_bank_id', $bankId)
                    ->whereNotNull('skill_part_id')
                    ->distinct()
                    ->pluck('skill_part_id');

                $originals = DB::table('skill_parts')->whereIn('id', $partIdsUsedByBank)->get();

                foreach ($originals as $original) {
                    $cloneId = DB::table('skill_parts')->insertGetId([
                        'question_bank_id' => $bankId,
                        'skill' => $original->skill,
                        'name' => $original->name,
                        'order' => $original->order,
                        'directions' => $original->directions,
                        'is_active' => $original->is_active,
                        'created_at' => $original->created_at,
                        'updated_at' => $original->updated_at,
                    ]);

                    DB::table('questions')
                        ->where('question_bank_id', $bankId)
                        ->where('skill_part_id', $original->id)
                        ->update(['skill_part_id' => $cloneId]);
                }
            }

            // Hapus part lama yang tidak lagi dipakai soal (tidak punya bank)
            DB::table('skill_parts')
                ->whereNull('question_bank_id')
                ->whereNotIn('id', DB::table('questions')->whereNotNull('skill_part_id')->pluck('skill_part_id'))
                ->delete();
        });

        Schema::table('skill_parts', function (Blueprint $table) {
            $table->foreignId('question_bank_id')->nullable(false)->change();
            $table->unique(['question_bank_id', 'skill', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('skill_parts', function (Blueprint $table) {
            $table->dropUnique('skill_parts_question_bank_id_skill_name_unique');
            $table->foreignId('question_bank_id')->nullable()->change();
        });

        // Re-create part global dari klon, lalu reassign soal
        DB::transaction(function () {
            $clones = DB::table('skill_parts')->whereNotNull('question_bank_id')->get();

            foreach ($clones as $clone) {
                $global = DB::table('skill_parts')
                    ->whereNull('question_bank_id')
                    ->where('skill', $clone->skill)
                    ->where('name', $clone->name)
                    ->first();

                if ($global === null) {
                    $globalId = DB::table('skill_parts')->insertGetId([
                        'question_bank_id' => null,
                        'skill' => $clone->skill,
                        'name' => $clone->name,
                        'order' => $clone->order,
                        'directions' => $clone->directions,
                        'is_active' => $clone->is_active,
                        'created_at' => $clone->created_at,
                        'updated_at' => $clone->updated_at,
                    ]);
                } else {
                    $globalId = $global->id;
                }

                DB::table('questions')->where('skill_part_id', $clone->id)->update(['skill_part_id' => $globalId]);
            }

            DB::table('skill_parts')->whereNotNull('question_bank_id')->delete();
        });

        Schema::table('skill_parts', function (Blueprint $table) {
            $table->dropColumn('question_bank_id');
            $table->unique(['skill', 'name']);
        });
    }
};
