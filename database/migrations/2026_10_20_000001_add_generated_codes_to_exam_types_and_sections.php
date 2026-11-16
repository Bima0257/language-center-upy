<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('urutan_kode', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('ruang', 50)->unique();
            $table->unsignedInteger('nilai_terakhir')->default(0);
            $table->timestamps();
        });

        DB::table('urutan_kode')->insert([
            'id' => (string) Str::uuid(),
            'ruang' => 'program_tes',
            'nilai_terakhir' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('program_tes', function (Blueprint $table): void {
            $table->string('kode', 3)->nullable()->after('nama');
        });

        Schema::table('section', function (Blueprint $table): void {
            $table->foreignUuid('program_tes_id')
                ->nullable()
                ->after('id')
                ->constrained('program_tes')
                ->restrictOnDelete();
        });

        $programTes = DB::table('program_tes')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'kode']);

        if ($programTes->count() > 999) {
            throw new RuntimeException('Jumlah program tes melebihi kapasitas kode 3 digit.');
        }

        $programTesKode = [];
        foreach ($programTes as $index => $dataProgramTes) {
            $kode = str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
            $programTesKode[(string) $dataProgramTes->id] = $kode;

            DB::table('program_tes')
                ->where('id', $dataProgramTes->id)
                ->update(['kode' => $kode]);
        }

        DB::table('urutan_kode')
            ->where('ruang', 'program_tes')
            ->update([
                'nilai_terakhir' => $programTes->count(),
                'updated_at' => now(),
            ]);

        $section = DB::table('section')
            ->orderBy('urutan')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'kode', 'program_tes_id']);

        if ($section->isNotEmpty() && $programTes->count() !== 1) {
            throw new RuntimeException('Section existing tidak dapat dipetakan otomatis ke beberapa program tes. Gunakan database fresh.');
        }

        $programTesDefaultId = $programTes->first()?->id;
        $sectionPerProgramTes = [];
        $kodeSectionLama = [];

        foreach ($section as $dataSection) {
            $programTesId = $dataSection->program_tes_id ?: $programTesDefaultId;

            if ($programTesId === null) {
                throw new RuntimeException('Section existing tidak memiliki program tes tujuan.');
            }

            $programTesId = (string) $programTesId;
            $sectionPerProgramTes[$programTesId] = ($sectionPerProgramTes[$programTesId] ?? 0) + 1;
            $nomorSection = $sectionPerProgramTes[$programTesId];

            if ($nomorSection > 999) {
                throw new RuntimeException('Jumlah section dalam satu program tes melebihi kapasitas kode 3 digit.');
            }

            $kodeProgramTes = $programTesKode[$programTesId] ?? null;

            if ($kodeProgramTes === null) {
                throw new RuntimeException('Program tes untuk section existing tidak ditemukan.');
            }

            $kodeLama = (string) $dataSection->kode;
            $kodeBaru = $kodeProgramTes.str_pad((string) $nomorSection, 3, '0', STR_PAD_LEFT);
            $kodeSectionLama[$programTesId.'|'.$kodeLama] = $kodeBaru;

            DB::table('section')
                ->where('id', $dataSection->id)
                ->update([
                    'program_tes_id' => $programTesId,
                    'kode' => $kodeBaru,
                ]);
        }

        foreach ($programTesKode as $programTesId => $kodeProgramTes) {
            DB::table('urutan_kode')->updateOrInsert(
                ['ruang' => 'section:'.$kodeProgramTes],
                [
                    'nilai_terakhir' => $sectionPerProgramTes[$programTesId] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        if ($kodeSectionLama !== []) {
            foreach (DB::table('aturan_penilaian')->get(['id', 'program_tes_id', 'kode_section']) as $aturan) {
                $kodeBaru = $kodeSectionLama[(string) $aturan->program_tes_id.'|'.(string) $aturan->kode_section] ?? null;

                if ($kodeBaru !== null) {
                    DB::table('aturan_penilaian')
                        ->where('id', $aturan->id)
                        ->update(['kode_section' => $kodeBaru]);
                }
            }
        }

        Schema::table('program_tes', function (Blueprint $table): void {
            $table->string('kode', 3)->nullable(false)->change();
            $table->unique('kode');
        });

        Schema::table('section', function (Blueprint $table): void {
            $table->dropUnique('section_kode_unique');
            $table->string('kode', 6)->nullable(false)->change();
            $table->unique('kode');
            $table->unique(['program_tes_id', 'nama'], 'section_program_tes_nama_unique');
            $table->foreignUuid('program_tes_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('section', function (Blueprint $table): void {
            $table->dropUnique('section_program_tes_nama_unique');
            $table->dropUnique('section_kode_unique');
            $table->dropForeign(['program_tes_id']);
            $table->dropColumn('program_tes_id');
            $table->string('kode', 50)->change();
            $table->unique('kode');
        });

        Schema::table('program_tes', function (Blueprint $table): void {
            $table->dropUnique('program_tes_kode_unique');
            $table->dropColumn('kode');
        });

        Schema::dropIfExists('urutan_kode');
    }
};
