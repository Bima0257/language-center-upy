<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_passage_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_bank_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->restrictOnDelete();
            $table->string('passage_requirement', 20)->default('optional');
            $table->timestamps();
            $table->unique(['question_bank_id', 'skill_id'], 'skill_passage_policies_bank_skill_unique');
        });

        Schema::create('skill_passage_media_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skill_passage_policy_id')->constrained()->cascadeOnDelete();
            $table->string('media_type', 20);
            $table->string('requirement', 20);
            $table->timestamps();
            $table->unique(['skill_passage_policy_id', 'media_type'], 'skill_passage_media_rules_policy_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_passage_media_rules');
        Schema::dropIfExists('skill_passage_policies');
    }
};
