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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username', 100)->nullable()->unique()->after('name');
            $table->boolean('status')->default(true)->after('aktif');
            $table->timestamp('last_login')->nullable()->after('status');
        });

        DB::table('users')->whereNull('status')->update(['status' => DB::raw('aktif')]);

        $used = [];

        DB::table('users')->select(['id', 'name', 'email'])->orderBy('id')->chunk(100, function ($users) use (&$used): void {
            foreach ($users as $user) {
                $base = Str::slug(Str::before((string) ($user->email ?: $user->name), '@'));

                if ($base === '') {
                    $base = 'user';
                }

                $username = $base;
                $suffix = 1;

                while (isset($used[$username]) || DB::table('users')->where('username', $username)->exists()) {
                    $username = $base.'-'.$suffix;
                    $suffix++;
                }

                $used[$username] = true;

                DB::table('users')->where('id', $user->id)->update(['username' => $username]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'status', 'last_login']);
        });
    }
};
