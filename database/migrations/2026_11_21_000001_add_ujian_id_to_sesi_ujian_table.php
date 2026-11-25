<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sesi_ujian', function (Blueprint $table): void {
            $table->uuid('slot_ujian_id')->nullable()->change();
            $table->foreignUuid('ujian_id')
                ->nullable()
                ->after('slot_ujian_id')
                ->constrained('ujian')
                ->restrictOnDelete();
            $table->index(['ujian_id', 'pengguna_id']);
        });

        $this->isiUjianId();
    }

    public function down(): void
    {
        Schema::table('sesi_ujian', function (Blueprint $table): void {
            $table->dropIndex(['ujian_id', 'pengguna_id']);
            $table->dropConstrainedForeignId('ujian_id');
        });

        Schema::table('sesi_ujian', function (Blueprint $table): void {
            $table->uuid('slot_ujian_id')->nullable(false)->change();
        });
    }

    private function isiUjianId(): void
    {
        DB::table('sesi_ujian')
            ->select(['id', 'slot_ujian_id'])
            ->whereNull('ujian_id')
            ->orderBy('id')
            ->chunkById(500, function ($sessions): void {
                $slotIds = $sessions
                    ->pluck('slot_ujian_id')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                if ($slotIds === []) {
                    return;
                }

                $ujianPerSlot = DB::table('slot_ujian')
                    ->join('periode_ujian', 'periode_ujian.id', '=', 'slot_ujian.periode_ujian_id')
                    ->whereIn('slot_ujian.id', $slotIds)
                    ->pluck('periode_ujian.ujian_id', 'slot_ujian.id');

                foreach ($sessions as $session) {
                    $ujianId = $ujianPerSlot[$session->slot_ujian_id] ?? null;

                    if ($ujianId === null) {
                        continue;
                    }

                    DB::table('sesi_ujian')
                        ->where('id', $session->id)
                        ->update(['ujian_id' => $ujianId]);
                }
            }, 'id');
    }
};
