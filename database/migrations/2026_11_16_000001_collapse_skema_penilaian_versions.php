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
        $this->konsolidasiSkema();

        if (! $this->hasIndex('skema_penilaian_program_tes_id_unique')) {
            Schema::table('skema_penilaian', function (Blueprint $table): void {
                $table->unique('program_tes_id');
            });
        }

        $kolomDihapus = ['versi', 'nama', 'status', 'diaktifkan_pada'];

        if (DB::connection()->getDriverName() !== 'sqlite') {
            $this->dropForeignIfExists('skema_penilaian_dibuat_oleh_foreign');
            $this->dropForeignIfExists('skema_penilaian_diaktifkan_oleh_foreign');
            $kolomDihapus[] = 'dibuat_oleh';
            $kolomDihapus[] = 'diaktifkan_oleh';
        }

        if ($this->hasIndex('skema_penilaian_program_tes_id_versi_unique')) {
            Schema::table('skema_penilaian', function (Blueprint $table): void {
                $table->dropUnique('skema_penilaian_program_tes_id_versi_unique');
            });
        }

        if ($this->hasIndex('skema_penilaian_program_tes_id_status_index')) {
            Schema::table('skema_penilaian', function (Blueprint $table): void {
                $table->dropIndex('skema_penilaian_program_tes_id_status_index');
            });
        }

        $kolomDihapus = array_values(array_filter(
            $kolomDihapus,
            fn (string $kolom): bool => Schema::hasColumn('skema_penilaian', $kolom),
        ));

        if ($kolomDihapus !== []) {
            Schema::table('skema_penilaian', function (Blueprint $table) use ($kolomDihapus): void {
                $table->dropColumn($kolomDihapus);
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('skema_penilaian_program_tes_id_unique')) {
            Schema::table('skema_penilaian', function (Blueprint $table): void {
                $table->dropUnique('skema_penilaian_program_tes_id_unique');
            });
        }

        Schema::table('skema_penilaian', function (Blueprint $table): void {
            if (! Schema::hasColumn('skema_penilaian', 'versi')) {
                $table->unsignedInteger('versi')->default(1);
            }

            if (! Schema::hasColumn('skema_penilaian', 'nama')) {
                $table->string('nama')->default('Skema Penilaian');
            }

            if (! Schema::hasColumn('skema_penilaian', 'status')) {
                $table->string('status', 20)->default('aktif');
            }

            if (! Schema::hasColumn('skema_penilaian', 'diaktifkan_pada')) {
                $table->timestamp('diaktifkan_pada')->nullable();
            }

            if (! Schema::hasColumn('skema_penilaian', 'dibuat_oleh')) {
                $table->foreignUuid('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('skema_penilaian', 'diaktifkan_oleh')) {
                $table->foreignUuid('diaktifkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            }
        });

        if (! $this->hasIndex('skema_penilaian_program_tes_id_versi_unique')) {
            Schema::table('skema_penilaian', function (Blueprint $table): void {
                $table->unique(['program_tes_id', 'versi']);
            });
        }

        if (! $this->hasIndex('skema_penilaian_program_tes_id_status_index')) {
            Schema::table('skema_penilaian', function (Blueprint $table): void {
                $table->index(['program_tes_id', 'status']);
            });
        }
    }

    private function hasIndex(string $indexName): bool
    {
        return collect(Schema::getIndexes('skema_penilaian'))
            ->contains(fn (array $index): bool => $index['name'] === $indexName);
    }

    private function dropForeignIfExists(string $foreignKey): void
    {
        $exists = collect(Schema::getForeignKeys('skema_penilaian'))
            ->contains(fn (array $foreign): bool => $foreign['name'] === $foreignKey);

        if (! $exists) {
            return;
        }

        Schema::table('skema_penilaian', function (Blueprint $table) use ($foreignKey): void {
            $table->dropForeign($foreignKey);
        });
    }

    private function konsolidasiSkema(): void
    {
        $programTes = DB::table('program_tes')->select(['id', 'nama'])->orderBy('id')->get();

        foreach ($programTes as $program) {
            $daftarSkema = DB::table('skema_penilaian')
                ->where('program_tes_id', $program->id)
                ->orderByDesc('versi')
                ->get();

            if ($daftarSkema->isEmpty()) {
                DB::table('skema_penilaian')->insert([
                    'id' => (string) Str::uuid(),
                    'program_tes_id' => $program->id,
                    'versi' => 1,
                    'nama' => 'Skema '.$program->nama,
                    'status' => 'aktif',
                    'metode_total' => 'jumlah',
                    'diaktifkan_pada' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                continue;
            }

            $skemaUtama = $daftarSkema->firstWhere('status', 'aktif') ?? $daftarSkema->first();

            foreach ($daftarSkema as $skema) {
                if ($skema->id === $skemaUtama->id) {
                    continue;
                }

                DB::table('sesi_ujian')
                    ->where('skema_penilaian_id', $skema->id)
                    ->update(['skema_penilaian_id' => $skemaUtama->id]);

                DB::table('hasil_penilaian')
                    ->where('skema_penilaian_id', $skema->id)
                    ->update(['skema_penilaian_id' => $skemaUtama->id]);

                DB::table('aturan_penilaian')
                    ->where('skema_penilaian_id', $skema->id)
                    ->delete();

                DB::table('interpretasi_skor')
                    ->where('skema_penilaian_id', $skema->id)
                    ->delete();

                DB::table('skema_penilaian')
                    ->where('id', $skema->id)
                    ->delete();
            }
        }
    }
};
