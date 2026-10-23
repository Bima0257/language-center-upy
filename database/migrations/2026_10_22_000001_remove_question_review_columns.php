<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('soal', function (Blueprint $table): void {
            $table->dropForeign(['peninjau_id']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'status',
                'peninjau_id',
                'ditinjau_pada',
                'catatan_tinjauan',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('soal', function (Blueprint $table): void {
            $table->enum('status', ['draft', 'approved', 'rejected'])->default('draft');
            $table->foreignUuid('peninjau_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditinjau_pada')->nullable();
            $table->text('catatan_tinjauan')->nullable();
            $table->index('status');
        });
    }
};
