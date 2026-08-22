<?php

namespace Tests\Feature\MasterData;

use App\Models\ExamType;
use App\Models\QuestionBank;
use App\Models\Skill;
use App\Models\SkillPart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkillPartsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function loginAs(string $role): User
    {
        $user = User::where('email', $role.'@toefl.test')->first();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        return $user;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/master-data/parts')->assertRedirect('/login');
    }

    public function test_wrong_role_is_forbidden(): void
    {
        $this->loginAs('student');

        $this->get('/admin/master-data/parts')->assertForbidden();
    }

    public function test_admin_can_access_page(): void
    {
        $this->loginAs('admin');

        $this->get('/admin/master-data/parts')->assertOk();
    }

    public function test_admin_can_filter_parts_by_bank(): void
    {
        $this->loginAs('admin');

        $bank = QuestionBank::where('name', 'Bank Soal 2022')->first();

        $this->get('/admin/master-data/parts?bank_id='.$bank->id)->assertOk();
    }

    public function test_creating_part_requires_bank(): void
    {
        $this->loginAs('admin');

        $skill = Skill::where('code', 'reading')->first();

        $this->post('/admin/master-data/parts', [
            'skill_id' => $skill->id,
            'name' => 'Part X',
            'order' => 1,
        ])->assertSessionHasErrors('question_bank_id');
    }

    public function test_admin_can_create_part_for_bank(): void
    {
        $this->loginAs('admin');

        $bank = QuestionBank::where('name', 'Bank Soal 2022')->first();
        $skill = Skill::where('code', 'reading')->first();

        $this->post('/admin/master-data/parts', [
            'question_bank_id' => $bank->id,
            'skill_id' => $skill->id,
            'name' => 'Part Baru',
            'order' => 9,
        ])->assertRedirect();

        $this->assertDatabaseHas('skill_parts', [
            'question_bank_id' => $bank->id,
            'skill_id' => $skill->id,
            'name' => 'Part Baru',
        ]);
    }

    public function test_duplicate_part_name_same_bank_skill_is_rejected(): void
    {
        $this->loginAs('admin');

        $bank = QuestionBank::where('name', 'Bank Soal 2022')->first();
        $skill = Skill::where('code', 'reading')->first();
        $existing = SkillPart::where('question_bank_id', $bank->id)->where('skill_id', $skill->id)->first();

        $this->post('/admin/master-data/parts', [
            'question_bank_id' => $bank->id,
            'skill_id' => $skill->id,
            'name' => $existing->name,
            'order' => 1,
        ])->assertSessionHasErrors('name');
    }

    public function test_same_part_name_allowed_in_other_bank(): void
    {
        $this->loginAs('admin');

        $bank = QuestionBank::where('name', 'Bank Soal 2022')->first();
        $skill = Skill::where('code', 'reading')->first();
        $existing = SkillPart::where('question_bank_id', $bank->id)->where('skill_id', $skill->id)->first();

        $otherBank = QuestionBank::create([
            'name' => 'Bank Baru',
            'exam_type_id' => ExamType::first()->id,
            'is_active' => true,
        ]);

        $this->post('/admin/master-data/parts', [
            'question_bank_id' => $otherBank->id,
            'skill_id' => $skill->id,
            'name' => $existing->name,
            'order' => 1,
        ])->assertRedirect();
    }
}
