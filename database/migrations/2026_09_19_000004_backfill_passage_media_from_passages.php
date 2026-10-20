<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('passages')
            ->whereNotNull('audio_url')
            ->orderBy('id')
            ->chunkById(500, function ($passages) use ($now) {
                foreach ($passages as $passage) {
                    DB::table('passage_media')->insert([
                        'passage_id' => $passage->id,
                        'media_type' => 'audio',
                        'file_path' => $passage->audio_url,
                        'mime_type' => null,
                        'size_bytes' => null,
                        'duration_seconds' => null,
                        'sort_order' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });

        DB::table('passages')
            ->whereNotNull('image_url')
            ->orderBy('id')
            ->chunkById(500, function ($passages) use ($now) {
                foreach ($passages as $passage) {
                    DB::table('passage_media')->insert([
                        'passage_id' => $passage->id,
                        'media_type' => 'image',
                        'file_path' => $passage->image_url,
                        'mime_type' => null,
                        'size_bytes' => null,
                        'duration_seconds' => null,
                        'sort_order' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('passage_media')->delete();
    }
};
