<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $columns = [
        'phone' => 'nomor_telepon',
        'photo' => 'foto',
        'is_active' => 'aktif',
        'google_avatar' => 'avatar_google',
    ];

    public function up(): void
    {
        $this->renameColumns($this->columns);
    }

    public function down(): void
    {
        $this->renameColumns(array_flip($this->columns));
    }

    /**
     * @param  array<string, string>  $columns
     */
    private function renameColumns(array $columns): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            foreach ($columns as $oldColumn => $newColumn) {
                if (Schema::hasColumn('users', $oldColumn) && ! Schema::hasColumn('users', $newColumn)) {
                    Schema::table('users', function (Blueprint $table) use ($oldColumn, $newColumn): void {
                        $table->renameColumn($oldColumn, $newColumn);
                    });
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
};
