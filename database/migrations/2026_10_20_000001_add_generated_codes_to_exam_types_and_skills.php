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
            'ruang' => 'jenis_tes',
            'nilai_terakhir' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('jenis_tes', function (Blueprint $table): void {
            $table->string('kode', 3)->nullable()->after('nama');
        });

        Schema::table('keterampilan', function (Blueprint $table): void {
            $table->foreignUuid('jenis_tes_id')
                ->nullable()
                ->after('id')
                ->constrained('jenis_tes')
                ->restrictOnDelete();
        });

        $jenisTes = DB::table('jenis_tes')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'kode']);

        if ($jenisTes->count() > 999) {
            throw new RuntimeException('Jumlah jenis tes melebihi kapasitas kode 3 digit.');
        }

        $jenisTesKode = [];
        foreach ($jenisTes as $index => $dataJenisTes) {
            $kode = str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
            $jenisTesKode[(string) $dataJenisTes->id] = $kode;

            DB::table('jenis_tes')
                ->where('id', $dataJenisTes->id)
                ->update(['kode' => $kode]);
        }

        DB::table('urutan_kode')
            ->where('ruang', 'jenis_tes')
            ->update([
                'nilai_terakhir' => $jenisTes->count(),
                'updated_at' => now(),
            ]);

        $keterampilan = DB::table('keterampilan')
            ->orderBy('urutan')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'kode', 'jenis_tes_id']);

        if ($keterampilan->isNotEmpty() && $jenisTes->count() !== 1) {
            throw new RuntimeException('Keterampilan existing tidak dapat dipetakan otomatis ke beberapa jenis tes. Gunakan database fresh.');
        }

        $jenisTesDefaultId = $jenisTes->first()?->id;
        $keterampilanPerJenisTes = [];
        $kodeKeterampilanLama = [];

        foreach ($keterampilan as $dataKeterampilan) {
            $jenisTesId = $dataKeterampilan->jenis_tes_id ?: $jenisTesDefaultId;

            if ($jenisTesId === null) {
                throw new RuntimeException('Keterampilan existing tidak memiliki jenis tes tujuan.');
            }

            $jenisTesId = (string) $jenisTesId;
            $keterampilanPerJenisTes[$jenisTesId] = ($keterampilanPerJenisTes[$jenisTesId] ?? 0) + 1;
            $nomorKeterampilan = $keterampilanPerJenisTes[$jenisTesId];

            if ($nomorKeterampilan > 999) {
                throw new RuntimeException('Jumlah keterampilan dalam satu jenis tes melebihi kapasitas kode 3 digit.');
            }

            $kodeJenisTes = $jenisTesKode[$jenisTesId] ?? null;

            if ($kodeJenisTes === null) {
                throw new RuntimeException('Jenis tes untuk keterampilan existing tidak ditemukan.');
            }

            $kodeLama = (string) $dataKeterampilan->kode;
            $kodeBaru = $kodeJenisTes.str_pad((string) $nomorKeterampilan, 3, '0', STR_PAD_LEFT);
            $kodeKeterampilanLama[$jenisTesId.'|'.$kodeLama] = $kodeBaru;

            DB::table('keterampilan')
                ->where('id', $dataKeterampilan->id)
                ->update([
                    'jenis_tes_id' => $jenisTesId,
                    'kode' => $kodeBaru,
                ]);
        }

        foreach ($jenisTesKode as $jenisTesId => $kodeJenisTes) {
            DB::table('urutan_kode')->updateOrInsert(
                ['ruang' => 'keterampilan:'.$kodeJenisTes],
                [
                    'nilai_terakhir' => $keterampilanPerJenisTes[$jenisTesId] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        if ($kodeKeterampilanLama !== []) {
            foreach (DB::table('aturan_penilaian')->get(['id', 'jenis_tes_id', 'kode_keterampilan']) as $aturan) {
                $kodeBaru = $kodeKeterampilanLama[(string) $aturan->jenis_tes_id.'|'.(string) $aturan->kode_keterampilan] ?? null;

                if ($kodeBaru !== null) {
                    DB::table('aturan_penilaian')
                        ->where('id', $aturan->id)
                        ->update(['kode_keterampilan' => $kodeBaru]);
                }
            }
        }

        Schema::table('jenis_tes', function (Blueprint $table): void {
            $table->string('kode', 3)->nullable(false)->change();
            $table->unique('kode');
        });

        Schema::table('keterampilan', function (Blueprint $table): void {
            $table->dropUnique('keterampilan_kode_unique');
            $table->string('kode', 6)->nullable(false)->change();
            $table->unique('kode');
            $table->unique(['jenis_tes_id', 'nama'], 'keterampilan_jenis_tes_nama_unique');
            $table->foreignUuid('jenis_tes_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('keterampilan', function (Blueprint $table): void {
            $table->dropUnique('keterampilan_jenis_tes_nama_unique');
            $table->dropUnique('keterampilan_kode_unique');
            $table->dropForeign(['jenis_tes_id']);
            $table->dropColumn('jenis_tes_id');
            $table->string('kode', 50)->change();
            $table->unique('kode');
        });

        Schema::table('jenis_tes', function (Blueprint $table): void {
            $table->dropUnique('jenis_tes_kode_unique');
            $table->dropColumn('kode');
        });

        Schema::dropIfExists('urutan_kode');
    }
};
