<?php

namespace Tests\Feature\Exam;

use App\Models\Exam;
use App\Models\ExamType;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamsTest extends TestCase
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
        $this->get('/admin/exams')->assertRedirect('/login');
    }

    public function test_wrong_role_is_forbidden(): void
    {
        $this->loginAs('student');

        $this->get('/admin/exams')->assertForbidden();
    }

    public function test_admin_can_access_page(): void
    {
        $this->loginAs('admin');

        $this->get('/admin/exams')->assertOk();
    }

    public function test_creating_exam_builds_sections_per_skill_and_bank(): void
    {
        $this->loginAs('admin');

        $examType = ExamType::where('name', 'TOEFL iBT')->first();

        $this->post('/admin/exams', [
            'exam_type_id' => $examType->id,
            'title' => 'Ujian Baru 2026',
            'description' => 'Deskripsi',
            'mode' => 'tryout',
            'duration_minutes' => 120,
        ])->assertRedirect();

        $exam = Exam::where('title', 'Ujian Baru 2026')->first();

        $this->assertNotNull($exam);

        $banks = QuestionBank::where('exam_type_id', $examType->id)->get();

        $this->assertGreaterThanOrEqual(1, $exam->sections->count());

        foreach ($exam->sections as $section) {
            $this->assertNotNull($section->question_bank_id);
            $this->assertTrue(
                $banks->contains('id', $section->question_bank_id),
                'Section harus terikat bank yang sejenis dengan exam.',
            );
        }
    }

    public function test_admin_can_create_section_for_bank(): void
    {
        $this->loginAs('admin');

        $examType = ExamType::where('name', 'TOEFL iBT')->first();
        $bank = QuestionBank::where('name', 'Bank Soal 2022')->first();

        $exam = Exam::create([
            'exam_type_id' => $examType->id,
            'title' => 'Ujian Section Manual',
            'mode' => 'tryout',
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        $this->post("/admin/exams/{$exam->id}/sections", [
            'question_bank_id' => $bank->id,
            'skill' => 'reading',
            'title' => 'Reading Manual',
            'order' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('exam_sections', [
            'exam_id' => $exam->id,
            'question_bank_id' => $bank->id,
            'title' => 'Reading Manual',
        ]);
    }

    public function test_creating_section_requires_bank(): void
    {
        $this->loginAs('admin');

        $examType = ExamType::where('name', 'TOEFL iBT')->first();

        $exam = Exam::create([
            'exam_type_id' => $examType->id,
            'title' => 'Ujian Tanpa Bank',
            'mode' => 'tryout',
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        $this->post("/admin/exams/{$exam->id}/sections", [
            'skill' => 'reading',
            'title' => 'Reading Tanpa Bank',
            'order' => 1,
        ])->assertSessionHasErrors('question_bank_id');

        $this->assertDatabaseMissing('exam_sections', [
            'title' => 'Reading Tanpa Bank',
        ]);
    }
}
