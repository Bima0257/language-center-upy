<?php

namespace Tests\Feature\MasterData;

use App\Models\QuestionBank;
use App\Models\Skill;
use App\Models\SkillPart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function loginAdmin(): void
    {
        $user = User::where('email', 'admin@toefl.test')->firstOrFail();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect();
    }

    public function test_part_store_menempatkan_urutan_otomatis_di_akhir(): void
    {
        $this->loginAdmin();

        $bank = QuestionBank::where('name', 'Bank Soal 2022')->firstOrFail();
        $skill = Skill::where('code', 'reading')->firstOrFail();
        $lastOrder = SkillPart::where('question_bank_id', $bank->id)
            ->where('skill_id', $skill->id)
            ->max('order');

        $this->post('/admin/master-data/parts', [
            'question_bank_id' => $bank->id,
            'skill_id' => $skill->id,
            'name' => 'Part Auto Order',
            'directions' => 'Instruksi test.',
            'is_active' => true,
        ])->assertRedirect();

        $part = SkillPart::where('question_bank_id', $bank->id)
            ->where('skill_id', $skill->id)
            ->where('name', 'Part Auto Order')
            ->firstOrFail();

        $this->assertSame((int) $lastOrder + 1, (int) $part->order);
    }

    public function test_part_reorder_mengubah_urutan_sesuai_urutan_baru(): void
    {
        $this->loginAdmin();

        $bank = QuestionBank::where('name', 'Bank Soal 2022')->firstOrFail();
        $skill = Skill::where('code', 'reading')->firstOrFail();
        $parts = SkillPart::where('question_bank_id', $bank->id)
            ->where('skill_id', $skill->id)
            ->orderBy('order')
            ->get();

        $this->assertGreaterThan(1, $parts->count());

        $reversed = $parts->pluck('id')->reverse()->values()->all();

        $this->post('/admin/master-data/parts/reorder', [
            'parts' => $reversed,
        ])->assertRedirect();

        $updated = SkillPart::whereIn('id', $reversed)
            ->orderBy('order')
            ->pluck('id')
            ->all();

        $this->assertSame($reversed, $updated);
    }
}
