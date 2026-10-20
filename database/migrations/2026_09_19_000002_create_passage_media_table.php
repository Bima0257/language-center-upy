<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passage_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('passage_id')->constrained()->cascadeOnDelete();
            $table->string('media_type', 20);
            $table->string('file_path', 255);
            $table->string('original_name', 255)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->index(['passage_id', 'media_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passage_media');
    }
};
