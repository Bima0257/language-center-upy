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
        Schema::table('aturan_penilaian', function (Blueprint $table): void {
            $table->foreignUuid('skema_penilaian_id')
                ->nullable()
                ->after('id')
                ->constrained('skema_penilaian')
                ->cascadeOnDelete();
            $table->foreignUuid('section_id')
                ->nullable()
                ->after('kode_section')
                ->constrained('section')
                ->restrictOnDelete();
            $table->integer('skor_skala_minimum')->default(0)->after('skor_mentah_maksimum');
            $table->index('program_tes_id', 'aturan_penilaian_program_tes_id_index');
        });

        Schema::table('aturan_penilaian', function (Blueprint $table): void {
            $table->dropUnique('aturan_penilaian_program_tes_id_kode_section_unique');
            $table->unique(['skema_penilaian_id', 'section_id'], 'aturan_penilaian_skema_section_unique');
            $table->index('skema_penilaian_id');
        });

        Schema::table('interpretasi_skor', function (Blueprint $table): void {
            $table->foreignUuid('skema_penilaian_id')
                ->nullable()
                ->after('id')
                ->constrained('skema_penilaian')
                ->cascadeOnDelete();
            $table->index('program_tes_id', 'interpretasi_skor_program_tes_id_index');
        });

        Schema::table('interpretasi_skor', function (Blueprint $table): void {
            $table->dropUnique('interpretasi_skor_program_tes_id_skor_minimum_unique');
            $table->unique(['skema_penilaian_id', 'skor_minimum'], 'interpretasi_skor_skema_minimum_unique');
            $table->index('skema_penilaian_id');
        });

        $this->backfillSchemes();
    }

    public function down(): void
    {
        Schema::table('interpretasi_skor', function (Blueprint $table): void {
            $table->dropUnique('interpretasi_skor_skema_minimum_unique');
            $table->dropIndex(['skema_penilaian_id']);
            $table->dropConstrainedForeignId('skema_penilaian_id');
            $table->unique(['program_tes_id', 'skor_minimum']);
            $table->dropIndex('interpretasi_skor_program_tes_id_index');
        });

        Schema::table('aturan_penilaian', function (Blueprint $table): void {
            $table->dropUnique('aturan_penilaian_skema_section_unique');
            $table->dropIndex(['skema_penilaian_id']);
            $table->dropConstrainedForeignId('skema_penilaian_id');
            $table->dropConstrainedForeignId('section_id');
            $table->dropColumn('skor_skala_minimum');
            $table->unique(['program_tes_id', 'kode_section']);
            $table->dropIndex('aturan_penilaian_program_tes_id_index');
        });

        DB::table('skema_penilaian')->delete();
    }

    private function backfillSchemes(): void
    {
        $programTesIds = array_values(array_unique(array_merge(
            DB::table('aturan_penilaian')->pluck('program_tes_id')->all(),
            DB::table('interpretasi_skor')->pluck('program_tes_id')->all(),
        )));

        foreach ($programTesIds as $programTesId) {
            $programTes = DB::table('program_tes')->where('id', $programTesId)->first();
            $skemaId = (string) Str::uuid();

            DB::table('skema_penilaian')->insert([
                'id' => $skemaId,
                'program_tes_id' => $programTesId,
                'versi' => 1,
                'nama' => 'Skema '.($programTes->nama ?? 'Penilaian').' v1',
                'status' => 'aktif',
                'metode_total' => 'jumlah',
                'diaktifkan_pada' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $aturanRows = DB::table('aturan_penilaian')
                ->where('program_tes_id', $programTesId)
                ->get();

            foreach ($aturanRows as $aturan) {
                $section = DB::table('section')
                    ->where('program_tes_id', $programTesId)
                    ->where('kode', $aturan->kode_section)
                    ->first();

                $tabel = json_decode((string) $aturan->tabel_konversi, true);
                $nilaiTabel = is_array($tabel) ? array_map('intval', array_values($tabel)) : [];
                $skalaMinimum = $nilaiTabel !== [] ? min($nilaiTabel) : 0;
                $skalaMaksimum = $nilaiTabel !== [] ? max($nilaiTabel) : (int) $aturan->skor_skala_maksimum;

                DB::table('aturan_penilaian')
                    ->where('id', $aturan->id)
                    ->update([
                        'skema_penilaian_id' => $skemaId,
                        'section_id' => $section->id ?? null,
                        'skor_skala_minimum' => $skalaMinimum,
                        'skor_skala_maksimum' => $skalaMaksimum,
                    ]);
            }

            DB::table('interpretasi_skor')
                ->where('program_tes_id', $programTesId)
                ->update(['skema_penilaian_id' => $skemaId]);

            $aturanTersimpan = DB::table('aturan_penilaian')
                ->where('skema_penilaian_id', $skemaId)
                ->get();

            DB::table('skema_penilaian')
                ->where('id', $skemaId)
                ->update([
                    'skala_total_minimum' => $aturanTersimpan->sum('skor_skala_minimum'),
                    'skala_total_maksimum' => $aturanTersimpan->sum('skor_skala_maksimum'),
                    'updated_at' => now(),
                ]);
        }
    }
};
