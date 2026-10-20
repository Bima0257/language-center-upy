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
            $table->dropColumn(['type', 'audio_url', 'image_url']);
        });
    }

    public function down(): void
    {
        Schema::table('passages', function (Blueprint $table) {
            $table->enum('type', ['text', 'audio', 'image'])->default('text');
            $table->string('audio_url')->nullable();
            $table->string('image_url')->nullable();
        });

        $passages = DB::table('passages')->orderBy('id')->get();

        foreach ($passages as $passage) {
            $audioPath = DB::table('passage_media')
                ->where('passage_id', $passage->id)
                ->where('media_type', 'audio')
                ->value('file_path');
            $imagePath = DB::table('passage_media')
                ->where('passage_id', $passage->id)
                ->where('media_type', 'image')
                ->value('file_path');

            DB::table('passages')->where('id', $passage->id)->update([
                'type' => $audioPath !== null ? 'audio' : ($imagePath !== null ? 'image' : 'text'),
                'audio_url' => $audioPath,
                'image_url' => $imagePath,
            ]);
        }
    }
};
