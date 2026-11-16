<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $programTesIds = DB::table('section')
                ->select('program_tes_id')
                ->distinct()
                ->orderBy('program_tes_id')
                ->pluck('program_tes_id');

            foreach ($programTesIds as $programTesId) {
                $section = DB::table('section')
                    ->where('program_tes_id', $programTesId)
                    ->orderBy('urutan')
                    ->orderBy('id')
                    ->get(['id']);

                foreach ($section as $index => $dataSection) {
                    DB::table('section')
                        ->where('id', $dataSection->id)
                        ->update(['urutan' => -($index + 1)]);
                }

                foreach ($section as $index => $dataSection) {
                    DB::table('section')
                        ->where('id', $dataSection->id)
                        ->update(['urutan' => $index + 1]);
                }
            }
        });

        Schema::table('section', function (Blueprint $table): void {
            $table->unique(['program_tes_id', 'urutan'], 'section_program_tes_urutan_unique');
        });
    }

    public function down(): void
    {
        Schema::table('section', function (Blueprint $table): void {
            $table->dropUnique('section_program_tes_urutan_unique');
        });
    }
};
