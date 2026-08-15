<?php

namespace Tests\Feature\Exam;

use App\Enums\SkillCode;
use App\Models\ExamType;
use App\Models\Passage;
use App\Models\QuestionBank;
use App\Models\SkillPart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentLibraryTest extends TestCase
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

    private function bank(): QuestionBank
    {
        return QuestionBank::firstOrCreate(['name' => 'Bank Soal Demo'], [
            'exam_type_id' => ExamType::first()->id,
            'is_active' => true,
        ]);
    }

    private function readingPart(): SkillPart
    {
        return SkillPart::where('question_bank_id', $this->bank()->id)
            ->where('skill', SkillCode::READING)
            ->first();
    }

    private function listeningPart(): SkillPart
    {
        return SkillPart::where('question_bank_id', $this->bank()->id)
            ->where('skill', SkillCode::LISTENING)
            ->first();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/content-library')->assertRedirect('/login');
    }

    public function test_wrong_role_is_forbidden(): void
    {
        $this->loginAs('student');

        $this->get('/content-library')->assertForbidden();
    }

    public function test_instructor_can_access_page(): void
    {
        $this->loginAs('instructor');

        $this->get('/content-library')->assertOk();
    }

    public function test_instructor_can_store_questions_with_existing_passage(): void
    {
        $this->loginAs('instructor');

        $passage = Passage::where('type', 'text')->first();

        $this->post('/content-library', [
            'question_bank_id' => $this->bank()->id,
            'passage_id' => $passage->id,
            'questions' => [
                [
                    'skill' => 'reading',
                    'skill_part_id' => $this->readingPart()->id,
                    'question_text' => 'What is the main topic?',
                    'option_a' => 'A',
                    'option_b' => 'B',
                    'option_c' => 'C',
                    'option_d' => 'D',
                    'correct_answer' => 'A',
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('questions', [
            'question_text' => 'What is the main topic?',
            'skill' => 'reading',
            'status' => 'draft',
        ]);
    }

    public function test_listening_question_requires_audio_passage(): void
    {
        $this->loginAs('instructor');

        $textPassage = Passage::where('type', 'text')->first();

        $this->post('/content-library', [
            'question_bank_id' => $this->bank()->id,
            'passage_id' => $textPassage->id,
            'questions' => [
                [
                    'skill' => 'listening',
                    'skill_part_id' => $this->listeningPart()->id,
                    'question_text' => 'What is the lecture about?',
                    'option_a' => 'A',
                    'option_b' => 'B',
                    'option_c' => 'C',
                    'option_d' => 'D',
                    'correct_answer' => 'A',
                ],
            ],
        ])->assertSessionHasErrors('questions');

        $this->assertDatabaseMissing('questions', [
            'question_text' => 'What is the lecture about?',
        ]);
    }

    public function test_question_cannot_use_part_from_other_bank(): void
    {
        $this->loginAs('instructor');

        $otherBank = QuestionBank::create([
            'name' => 'Bank Soal Lain',
            'exam_type_id' => ExamType::first()->id,
            'is_active' => true,
        ]);

        $foreignPart = SkillPart::create([
            'question_bank_id' => $otherBank->id,
            'skill' => SkillCode::READING,
            'name' => 'Part 1',
            'order' => 1,
            'is_active' => true,
        ]);

        $this->post('/content-library', [
            'question_bank_id' => $this->bank()->id,
            'questions' => [
                [
                    'skill' => 'reading',
                    'skill_part_id' => $foreignPart->id,
                    'question_text' => 'Soal memakai part bank lain',
                    'option_a' => 'A',
                    'option_b' => 'B',
                    'option_c' => 'C',
                    'option_d' => 'D',
                    'correct_answer' => 'A',
                ],
            ],
        ])->assertSessionHasErrors('questions');

        $this->assertDatabaseMissing('questions', [
            'question_text' => 'Soal memakai part bank lain',
        ]);
    }

    public function test_reading_question_requires_all_options(): void
    {
        $this->loginAs('instructor');

        $this->post('/content-library', [
            'question_bank_id' => $this->bank()->id,
            'questions' => [
                [
                    'skill' => 'reading',
                    'skill_part_id' => $this->readingPart()->id,
                    'question_text' => 'Pertanyaan tanpa opsi lengkap',
                    'option_a' => 'A',
                    'option_b' => 'B',
                    'option_c' => 'C',
                    'option_d' => '',
                    'correct_answer' => 'A',
                ],
            ],
        ])->assertSessionHasErrors('questions');

        $this->assertDatabaseCount('questions', 8);
    }
}
