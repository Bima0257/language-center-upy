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
        Schema::create('permission_categories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('module');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->boolean('is_system')->default(false);
            $table->boolean('is_default')->default(false);
        });

        Schema::table('permissions', function (Blueprint $table): void {
            $table->foreignUuid('permission_category_id')->nullable()->after('guard_name')->constrained('permission_categories')->nullOnDelete();
            $table->string('route_name')->nullable()->after('permission_category_id')->index();
            $table->string('action')->nullable()->after('route_name');
            $table->boolean('is_system')->default(true)->after('action');
            $table->string('category_assignment_source')->default('scanner')->after('is_system');
            $table->timestamp('last_scanned_at')->nullable()->after('category_assignment_source');
        });

        DB::table('permission_categories')->insert([
            'id' => (string) Str::uuid(),
            'code' => 'uncategorized',
            'name' => 'Uncategorized',
            'module' => 'System',
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('permission_category_id');
            $table->dropColumn(['route_name', 'action', 'is_system', 'category_assignment_source', 'last_scanned_at']);
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropColumn(['is_system', 'is_default']);
        });

        Schema::dropIfExists('permission_categories');
    }
};
