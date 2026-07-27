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
            $table->string('type', 50)->after('title')->default('text');
        });

        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE passages ADD CONSTRAINT passages_type_check CHECK (type IN ('text','audio','image','prompt'))");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE passages DROP CONSTRAINT IF EXISTS passages_type_check');
        }

        Schema::table('passages', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
