<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE questions ADD COLUMN tmp_skill VARCHAR(50)');

            DB::statement('UPDATE questions SET tmp_skill = skill');

            DB::statement('ALTER TABLE questions DROP COLUMN skill');

            DB::statement('ALTER TABLE questions RENAME COLUMN tmp_skill TO skill');
        } else {
            DB::statement('ALTER TABLE questions MODIFY skill VARCHAR(50) NULL');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE questions ADD COLUMN tmp_skill2 VARCHAR(50) NOT NULL DEFAULT ' . "''" . '');

            DB::statement('UPDATE questions SET tmp_skill2 = COALESCE(skill, ' . "''" . ')');

            DB::statement('ALTER TABLE questions DROP COLUMN skill');

            DB::statement('ALTER TABLE questions RENAME COLUMN tmp_skill2 TO skill');
        } else {
            DB::statement('ALTER TABLE questions MODIFY skill VARCHAR(50) NOT NULL');
        }
    }
};
