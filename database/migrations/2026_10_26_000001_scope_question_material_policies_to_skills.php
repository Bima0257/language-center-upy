<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->collapsePoliciesPerSkill();

        Schema::table('kebijakan_materi_soal', function (Blueprint $table): void {
            $table->dropForeign(['bank_soal_id']);
            $table->dropUnique('kebijakan_materi_soal_bank_skill_unique');
            $table->dropColumn('bank_soal_id');
            $table->unique('keterampilan_id', 'kebijakan_materi_soal_skill_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('kebijakan_materi_soal', 'bank_soal_id')) {
            Schema::table('kebijakan_materi_soal', function (Blueprint $table): void {
                $table->foreignUuid('bank_soal_id')->nullable();
            });
        }

        Schema::table('kebijakan_materi_soal', function (Blueprint $table): void {
            $table->index('keterampilan_id', 'kebijakan_materi_soal_skill_index');
            $table->dropUnique('kebijakan_materi_soal_skill_unique');
        });

        $this->expandPoliciesPerBank();

        Schema::table('kebijakan_materi_soal', function (Blueprint $table): void {
            $table->foreign('bank_soal_id')->references('id')->on('bank_soal')->cascadeOnDelete();
            $table->unique(['bank_soal_id', 'keterampilan_id'], 'kebijakan_materi_soal_bank_skill_unique');
        });
    }

    private function collapsePoliciesPerSkill(): void
    {
        $policies = DB::table('kebijakan_materi_soal')
            ->orderBy('keterampilan_id')
            ->orderBy('id')
            ->get();
        $rulesByPolicy = DB::table('aturan_media_materi_soal')
            ->orderBy('kebijakan_materi_soal_id')
            ->orderBy('jenis_media')
            ->get()
            ->groupBy('kebijakan_materi_soal_id');
        $duplicateIds = [];

        foreach ($policies->groupBy('keterampilan_id') as $skillId => $skillPolicies) {
            $signature = null;

            foreach ($skillPolicies as $policy) {
                $currentSignature = $this->policySignature($policy, $rulesByPolicy->get($policy->id, new Collection));

                if ($signature === null) {
                    $signature = $currentSignature;

                    continue;
                }

                if ($signature !== $currentSignature) {
                    throw new RuntimeException(
                        "Policy materi soal untuk keterampilan {$skillId} berbeda antar bank soal; selesaikan konflik sebelum migrasi.",
                    );
                }

                $duplicateIds[] = $policy->id;
            }
        }

        if ($duplicateIds !== []) {
            DB::table('kebijakan_materi_soal')->whereIn('id', $duplicateIds)->delete();
        }
    }

    private function expandPoliciesPerBank(): void
    {
        $policies = DB::table('kebijakan_materi_soal')->get();

        foreach ($policies as $policy) {
            $skill = DB::table('keterampilan')->where('id', $policy->keterampilan_id)->first();

            if ($skill === null) {
                throw new RuntimeException(
                    "Keterampilan {$policy->keterampilan_id} tidak ditemukan saat rollback policy materi soal.",
                );
            }

            $bankIds = DB::table('bank_soal')
                ->where('jenis_tes_id', $skill->jenis_tes_id)
                ->orderBy('id')
                ->pluck('id');

            if ($bankIds->isEmpty()) {
                throw new RuntimeException(
                    "Tidak ada bank soal untuk jenis tes {$skill->jenis_tes_id} saat rollback policy materi soal.",
                );
            }

            $bankId = $bankIds->shift();
            DB::table('kebijakan_materi_soal')
                ->where('id', $policy->id)
                ->update(['bank_soal_id' => $bankId]);

            $rules = DB::table('aturan_media_materi_soal')
                ->where('kebijakan_materi_soal_id', $policy->id)
                ->get();

            foreach ($bankIds as $additionalBankId) {
                $newPolicyId = (string) Str::uuid();

                DB::table('kebijakan_materi_soal')->insert([
                    'id' => $newPolicyId,
                    'bank_soal_id' => $additionalBankId,
                    'keterampilan_id' => $policy->keterampilan_id,
                    'ketentuan_materi' => $policy->ketentuan_materi,
                    'created_at' => $policy->created_at,
                    'updated_at' => $policy->updated_at,
                ]);

                foreach ($rules as $rule) {
                    DB::table('aturan_media_materi_soal')->insert([
                        'id' => (string) Str::uuid(),
                        'kebijakan_materi_soal_id' => $newPolicyId,
                        'jenis_media' => $rule->jenis_media,
                        'ketentuan' => $rule->ketentuan,
                        'created_at' => $rule->created_at,
                        'updated_at' => $rule->updated_at,
                    ]);
                }
            }
        }
    }

    private function policySignature(object $policy, Collection $rules): string
    {
        $mediaSignature = $rules
            ->map(fn (object $rule): string => $rule->jenis_media.':'.$rule->ketentuan)
            ->implode('|');

        return $policy->ketentuan_materi.'|'.$mediaSignature;
    }
};
