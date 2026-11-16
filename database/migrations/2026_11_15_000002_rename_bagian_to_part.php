<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('bagian_soal', 'part_soal');
        Schema::rename('bagian_ujian', 'part_ujian');
        Schema::rename('soal_bagian_ujian', 'soal_part_ujian');
        Schema::rename('bagian_soal_ujian', 'part_soal_ujian');

        Schema::table('soal', function (Blueprint $table): void {
            $table->renameColumn('bagian_soal_id', 'part_soal_id');
        });

        Schema::table('sesi_ujian', function (Blueprint $table): void {
            $table->renameColumn('bagian_aktif_id', 'part_ujian_aktif_id');
        });

        Schema::table('soal_part_ujian', function (Blueprint $table): void {
            $table->renameColumn('bagian_ujian_id', 'part_ujian_id');
        });

        Schema::table('part_soal_ujian', function (Blueprint $table): void {
            $table->renameColumn('bagian_ujian_id', 'part_ujian_id');
            $table->renameColumn('bagian_soal_id', 'part_soal_id');
        });

        $this->renamePermissions();
        $this->renameActivityLogSubjects();
    }

    public function down(): void
    {
        Schema::table('part_soal_ujian', function (Blueprint $table): void {
            $table->renameColumn('part_ujian_id', 'bagian_ujian_id');
            $table->renameColumn('part_soal_id', 'bagian_soal_id');
        });

        Schema::table('soal_part_ujian', function (Blueprint $table): void {
            $table->renameColumn('part_ujian_id', 'bagian_ujian_id');
        });

        Schema::table('sesi_ujian', function (Blueprint $table): void {
            $table->renameColumn('part_ujian_aktif_id', 'bagian_aktif_id');
        });

        Schema::table('soal', function (Blueprint $table): void {
            $table->renameColumn('part_soal_id', 'bagian_soal_id');
        });

        Schema::rename('part_soal_ujian', 'bagian_soal_ujian');
        Schema::rename('soal_part_ujian', 'soal_bagian_ujian');
        Schema::rename('part_ujian', 'bagian_ujian');
        Schema::rename('part_soal', 'bagian_soal');

        $this->renamePermissions(true);
        $this->renameActivityLogSubjects(true);
    }

    private function renamePermissions(bool $reverse = false): void
    {
        $from = $reverse
            ? ['part-soal', 'part_soal', 'part-ujian', 'part_ujian', 'part', 'Part']
            : ['bagian-soal', 'bagian_soal', 'bagian-ujian', 'bagian_ujian', 'bagian', 'Bagian'];
        $to = $reverse
            ? ['bagian-soal', 'bagian_soal', 'bagian-ujian', 'bagian_ujian', 'bagian', 'Bagian']
            : ['part-soal', 'part_soal', 'part-ujian', 'part_ujian', 'part', 'Part'];

        $permissions = DB::table('permissions')
            ->where(function ($query) use ($from): void {
                foreach ($from as $value) {
                    $query->orWhere('name', 'like', '%'.$value.'%')
                        ->orWhere('route_name', 'like', '%'.$value.'%');
                }
            })
            ->get();

        foreach ($permissions as $permission) {
            DB::table('permissions')->where('id', $permission->id)->update([
                'name' => str_replace($from, $to, $permission->name),
                'route_name' => $permission->route_name !== null
                    ? str_replace($from, $to, $permission->route_name)
                    : null,
            ]);
        }

        $categories = DB::table('permission_categories')
            ->where(function ($query) use ($from): void {
                foreach ($from as $value) {
                    $query->orWhere('code', 'like', '%'.$value.'%')
                        ->orWhere('name', 'like', '%'.$value.'%');
                }
            })
            ->get();

        foreach ($categories as $category) {
            DB::table('permission_categories')->where('id', $category->id)->update([
                'code' => str_replace($from, $to, $category->code),
                'name' => str_replace($from, $to, $category->name),
            ]);
        }
    }

    private function renameActivityLogSubjects(bool $reverse = false): void
    {
        $map = $reverse
            ? [
                'App\\Models\\PartSoal' => 'App\\Models\\BagianSoal',
                'App\\Models\\PartUjian' => 'App\\Models\\BagianUjian',
                'App\\Models\\SoalPartUjian' => 'App\\Models\\SoalBagianUjian',
                'App\\Models\\PartSoalUjian' => 'App\\Models\\BagianSoalUjian',
            ]
            : [
                'App\\Models\\BagianSoal' => 'App\\Models\\PartSoal',
                'App\\Models\\BagianUjian' => 'App\\Models\\PartUjian',
                'App\\Models\\SoalBagianUjian' => 'App\\Models\\SoalPartUjian',
                'App\\Models\\BagianSoalUjian' => 'App\\Models\\PartSoalUjian',
            ];

        foreach ($map as $from => $to) {
            DB::table('activity_log')->where('subject_type', $from)->update(['subject_type' => $to]);
        }
    }
};
