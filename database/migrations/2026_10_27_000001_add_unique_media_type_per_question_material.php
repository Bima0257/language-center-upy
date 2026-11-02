<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasDuplicates = DB::table('media_materi_soal')
            ->select(['materi_soal_id', 'jenis_media'])
            ->groupBy(['materi_soal_id', 'jenis_media'])
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicates) {
            throw new RuntimeException(
                'Tidak dapat menambahkan unique media materi soal karena terdapat media duplikat.',
            );
        }

        Schema::table('media_materi_soal', function (Blueprint $table): void {
            $table->unique(
                ['materi_soal_id', 'jenis_media'],
                'media_materi_soal_material_type_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('media_materi_soal', function (Blueprint $table): void {
            $table->dropUnique('media_materi_soal_material_type_unique');
        });
    }
};
