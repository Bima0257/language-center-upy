<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->renamePermissions(false);
        $this->renamePermissionCategories(false);
        $this->renameActivityLogSubjects(false);

        if (Schema::hasTable('urutan_kode')) {
            DB::table('urutan_kode')
                ->where('ruang', 'jenis_tes')
                ->update(['ruang' => 'program_tes']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('urutan_kode')) {
            DB::table('urutan_kode')
                ->where('ruang', 'program_tes')
                ->update(['ruang' => 'jenis_tes']);
        }

        $this->renameActivityLogSubjects(true);
        $this->renamePermissionCategories(true);
        $this->renamePermissions(true);
    }

    private function renamePermissions(bool $reverse): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $from = $reverse ? 'program-tes' : 'jenis-tes';
        $to = $reverse ? 'jenis-tes' : 'program-tes';

        DB::table('permissions')
            ->where(function ($query) use ($from): void {
                $query->where('name', 'like', '%'.$from.'%')
                    ->orWhere('route_name', 'like', '%'.$from.'%');
            })
            ->orderBy('id')
            ->chunkById(100, function ($permissions) use ($from, $to): void {
                foreach ($permissions as $permission) {
                    DB::table('permissions')->where('id', $permission->id)->update([
                        'name' => str_replace($from, $to, $permission->name),
                        'route_name' => $permission->route_name !== null
                            ? str_replace($from, $to, $permission->route_name)
                            : null,
                    ]);
                }
            });
    }

    private function renamePermissionCategories(bool $reverse): void
    {
        if (! Schema::hasTable('permission_categories')) {
            return;
        }

        $fromCode = $reverse ? 'data-induk.program-tes' : 'data-induk.jenis-tes';
        $toCode = $reverse ? 'data-induk.jenis-tes' : 'data-induk.program-tes';
        $toName = $reverse ? 'Jenis Tes' : 'Program Tes';

        $category = DB::table('permission_categories')->where('code', $fromCode)->first();

        if ($category === null) {
            return;
        }

        $existing = DB::table('permission_categories')->where('code', $toCode)->first();

        if ($existing !== null) {
            DB::table('permissions')
                ->where('permission_category_id', $category->id)
                ->update(['permission_category_id' => $existing->id]);

            DB::table('permission_categories')->where('id', $category->id)->delete();

            return;
        }

        DB::table('permission_categories')->where('id', $category->id)->update([
            'code' => $toCode,
            'name' => $toName,
        ]);
    }

    private function renameActivityLogSubjects(bool $reverse): void
    {
        if (! Schema::hasTable('activity_log')) {
            return;
        }

        DB::table('activity_log')
            ->where('subject_type', $reverse ? 'App\\Models\\ProgramTes' : 'App\\Models\\JenisTes')
            ->update([
                'subject_type' => $reverse ? 'App\\Models\\JenisTes' : 'App\\Models\\ProgramTes',
            ]);
    }
};
