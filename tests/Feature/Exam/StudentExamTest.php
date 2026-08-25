<?php

namespace Tests\Feature\Exam;

use App\Enums\ExamMode;
use App\Enums\SkillCode;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\ExamScheduleSlot;
use App\Models\ExamSection;
use App\Models\ExamSession;
use App\Models\ExamType;
use App\Models\Passage;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Skill;
use App\Models\User;
use App\Modules\Schedule\Repositories\Contracts\SlotRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentExamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function loginAsStudent(): void
    {
        $user = User::where('email', 'student@toefl.test')->first();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
    }

    private function getTestFile(string $filename): UploadedFile
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_jpeg_');
        file_put_contents($tmpFile, base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAQgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAAyACgDASIAAhEBAxEB/8QAGAABAQEBAQAAAAAAAAAAAAAAAAUGBAf/xAAsEAABAwMCAwcFAAAAAAAAAAABAAIDBBEhBRIxBhNBUWFxgZGhFCIycfD/xAAYAQADAQEAAAAAAAAAAAAAAAABAgMABP/EABsRAQEAAgMBAAAAAAAAAAAAAAEAAhEDITES/9oADAMBAAIRAxEAPwD0yC2urKltNTxGSR2waPufAekUdZT1kPnU8zJGf1Ndf8AIQHUVdT4hS+TObRvBjkb1Y4cD+D0IjYpKC8b+3t54nAO/teA62h+P6W6tLZk7FBtK8ONi3l2OxB2I+yIAAAAAAAAAAAAAAAAAAAAAAAAAAAAf/9k='));

        return new UploadedFile($tmpFile, $filename, 'image/jpeg', null, true);
    }

    private function createExam(string $title, string $mode): Exam
    {
        $type = ExamType::firstOrCreate(['name' => 'TOEFL iBT'], [
            'max_strikes' => 3,
            'is_active' => true,
        ]);

        return Exam::create([
            'exam_type_id' => $type->id,
            'title' => $title,
            'mode' => $mode,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);
    }

    public function test_available_slots_contains_only_tryout(): void
    {
        $tryout = $this->createExam('Tryout Premium', ExamMode::TRYOUT->value);
        $official = $this->createExam('Ujian Resmi', ExamMode::OFFICIAL->value);

        $scheduleT = ExamSchedule::create([
            'exam_id' => $tryout->id,
            'title' => 'Gelombang Tryout',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'is_active' => true,
        ]);

        $scheduleO = ExamSchedule::create([
            'exam_id' => $official->id,
            'title' => 'Gelombang Resmi',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'is_active' => true,
        ]);

        ExamScheduleSlot::create([
            'exam_schedule_id' => $scheduleT->id,
            'date' => now()->toDateString(),
            'start_time' => now()->subMinutes(5)->format('H:i:s'),
            'end_time' => now()->addHours(2)->format('H:i:s'),
            'late_tolerance_minutes' => 15,
            'max_participants' => 30,
            'is_active' => true,
        ]);

        ExamScheduleSlot::create([
            'exam_schedule_id' => $scheduleO->id,
            'date' => now()->toDateString(),
            'start_time' => now()->subMinutes(5)->format('H:i:s'),
            'end_time' => now()->addHours(2)->format('H:i:s'),
            'late_tolerance_minutes' => 15,
            'max_participants' => 30,
            'is_active' => true,
        ]);

        $this->loginAsStudent();
        $this->get('/exam/available')->assertOk();

        $slots = app(SlotRepositoryInterface::class)->availableSlots();

        $this->assertCount(1, $slots);
        $this->assertEquals($tryout->id, $slots->first()->schedule->exam_id);
    }

    public function test_schedule_lists_only_official(): void
    {
        $tryout = $this->createExam('Tryout Premium', ExamMode::TRYOUT->value);
        $official = $this->createExam('Ujian Resmi', ExamMode::OFFICIAL->value);

        ExamSchedule::create([
            'exam_id' => $tryout->id,
            'title' => 'Gelombang Tryout',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'is_active' => true,
        ]);

        ExamSchedule::create([
            'exam_id' => $official->id,
            'title' => 'Gelombang Resmi',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'is_active' => true,
        ]);

        $this->loginAsStudent();
        $this->get('/exam/schedule')->assertOk();

        $schedules = ExamSchedule::where('is_active', true)
            ->where('end_date', '>=', now()->toDateString())
            ->whereHas('exam', fn ($q) => $q->where('mode', ExamMode::OFFICIAL))
            ->get();

        $this->assertCount(1, $schedules);
        $this->assertEquals($official->id, $schedules->first()->exam_id);
    }

    public function test_take_session_hides_correct_answer(): void
    {
        $type = ExamType::firstOrCreate(['name' => 'TOEFL iBT'], [
            'max_strikes' => 3,
            'is_active' => true,
        ]);

        $bank = QuestionBank::firstOrCreate(
            ['name' => 'Bank Ujian Resmi'],
            ['exam_type_id' => $type->id, 'is_active' => true],
        );

        $skill = Skill::firstOrCreate(
            ['code' => SkillCode::READING->value],
            [
                'name' => 'Reading',
                'order' => 1,
                'is_active' => true,
            ],
        );

        $passage = Passage::create([
            'title' => 'Passage untuk Ujian',
            'content_text' => '<p>Isi passage ujian resmi.</p>',
            'skill_id' => $skill->id,
        ]);

        $question = Question::create([
            'question_bank_id' => $bank->id,
            'passage_id' => $passage->id,
            'skill_id' => $skill->id,
            'question_text' => 'Apa isi passage tersebut?',
            'option_a' => 'Pilihan A',
            'option_b' => 'Pilihan B',
            'option_c' => 'Pilihan C',
            'option_d' => 'Pilihan D',
            'correct_answer' => 'A',
            'order' => 1,
            'status' => 'approved',
        ]);

        $exam = Exam::create([
            'exam_type_id' => $type->id,
            'title' => 'Ujian Resmi untuk Take Test',
            'mode' => ExamMode::OFFICIAL->value,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        $section = ExamSection::create([
            'exam_id' => $exam->id,
            'question_bank_id' => $bank->id,
            'skill_id' => $skill->id,
            'title' => 'Reading — Bank Ujian Resmi',
            'order' => 1,
            'total_questions' => 1,
        ]);

        $section->examSectionQuestions()->create([
            'question_id' => $question->id,
            'order' => 1,
        ]);

        $schedule = ExamSchedule::create([
            'exam_id' => $exam->id,
            'title' => 'Periode Take Test',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'is_active' => true,
        ]);

        $slot = ExamScheduleSlot::create([
            'exam_schedule_id' => $schedule->id,
            'date' => now()->toDateString(),
            'start_time' => now()->subMinutes(5)->format('H:i:s'),
            'end_time' => now()->addHours(2)->format('H:i:s'),
            'late_tolerance_minutes' => 15,
            'max_participants' => 30,
            'is_active' => true,
        ]);

        $this->loginAsStudent();

        ExamSession::where('user_id', User::where('email', 'student@toefl.test')->first()->id)
            ->where('status', 'in_progress')
            ->delete();

        Storage::fake('public');

        $selfiePath = 'selfies/test-selfie.jpg';
        Storage::disk('public')->put($selfiePath, file_get_contents('data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAAyACgDASIAAhEBAxEB/8QAGAABAQEBAQAAAAAAAAAAAAAAAAUGBAf/xAAsEAABAwMCAwcFAAAAAAAAAAABAAIDBBEhBRIxBhNBUWFxgZGhFCIycfD/xAAYAQADAQEAAAAAAAAAAAAAAAABAgMABP/EABsRAQEAAgMBAAAAAAAAAAAAAAEAAhEDITES/9oADAMBAAIRAxEAPwD0yC2urKltNTxGSR2waPufAekUdZT1kPnU8zJGf1Ndf8AIQHUVdT4hS+TObRvBjkb1Y4cD+D0IjYpKC8b+3t54nAO/teA62h+P6W6tLZk7FBtK8ONi3l2OxB2I+yIAAAAAAAAAAAAAAAAAAAAAAAAAAAAf/9k='));
        $startResponse = $this->post("/exam/slots/{$slot->id}/start", [
            'device_type' => 'desktop',
            'device_user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'selfie' => $this->getTestFile('selfie.jpg'),
        ]);
        $startResponse->assertRedirect();

        $session = ExamSession::where('user_id', User::where('email', 'student@toefl.test')->first()->id)
            ->where('exam_schedule_slot_id', $slot->id)
            ->first();

        $this->assertNotNull($session);

        $response = $this->get("/exam/session/{$session->id}");
        $response->assertOk();

        $response->assertDontSee('correct_answer', false);
    }

    public function test_start_rejects_mobile_or_tablet_device(): void
    {
        $type = ExamType::firstOrCreate(['name' => 'TOEFL iBT'], [
            'max_strikes' => 3,
            'is_active' => true,
        ]);

        $bank = QuestionBank::firstOrCreate(
            ['name' => 'Bank Ujian Resmi'],
            ['exam_type_id' => $type->id, 'is_active' => true],
        );

        $skill = Skill::firstOrCreate(
            ['code' => SkillCode::READING->value],
            [
                'name' => 'Reading',
                'order' => 1,
                'is_active' => true,
            ],
        );

        $passage = Passage::create([
            'title' => 'Passage untuk Ujian',
            'content_text' => '<p>Isi passage ujian resmi.</p>',
            'skill_id' => $skill->id,
        ]);

        $question = Question::create([
            'question_bank_id' => $bank->id,
            'passage_id' => $passage->id,
            'skill_id' => $skill->id,
            'question_text' => 'Apa isi passage tersebut?',
            'option_a' => 'Pilihan A',
            'option_b' => 'Pilihan B',
            'option_c' => 'Pilihan C',
            'option_d' => 'Pilihan D',
            'correct_answer' => 'A',
            'order' => 1,
            'status' => 'approved',
        ]);

        $exam = Exam::create([
            'exam_type_id' => $type->id,
            'title' => 'Ujian Resmi untuk Device Test',
            'mode' => ExamMode::OFFICIAL->value,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        $section = ExamSection::create([
            'exam_id' => $exam->id,
            'question_bank_id' => $bank->id,
            'skill_id' => $skill->id,
            'title' => 'Reading — Bank Ujian Resmi',
            'order' => 1,
            'total_questions' => 1,
        ]);

        $section->examSectionQuestions()->create([
            'question_id' => $question->id,
            'order' => 1,
        ]);

        $schedule = ExamSchedule::create([
            'exam_id' => $exam->id,
            'title' => 'Periode Device Test',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'is_active' => true,
        ]);

        $slot = ExamScheduleSlot::create([
            'exam_schedule_id' => $schedule->id,
            'date' => now()->toDateString(),
            'start_time' => now()->subMinutes(5)->format('H:i:s'),
            'end_time' => now()->addHours(2)->format('H:i:s'),
            'late_tolerance_minutes' => 15,
            'max_participants' => 30,
            'is_active' => true,
        ]);

        $this->loginAsStudent();

        ExamSession::where('user_id', User::where('email', 'student@toefl.test')->first()->id)
            ->where('status', 'in_progress')
            ->delete();

        $selfiePath = 'selfies/test-selfie-mobile.jpg';
        Storage::disk('public')->put($selfiePath, file_get_contents('data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAQgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAAyACgDASIAAhEBAxEB/8QAGAABAQEBAQAAAAAAAAAAAAAAAAUGBAf/xAAsEAABAwMCAwcFAAAAAAAAAAABAAIDBBEhBRIxBhNBUWFxgZGhFCIycfD/xAAYAQADAQEAAAAAAAAAAAAAAAABAgMABP/EABsRAQEAAgMBAAAAAAAAAAAAAAEAAhEDITES/9oADAMBAAIRAxEAPwD0yC2urKltNTxGSR2waPufAekUdZT1kPnU8zJGf1Ndf8AIQHUVdT4hS+TObRvBjkb1Y4cD+D0IjYpKC8b+3t54nAO/teA62h+P6W6tLZk7FBtK8ONi3l2OxB2I+yIAAAAAAAAAAAAAAAAAAAAAAAAAAAAf/9k='));
        $startResponse = $this->post("/exam/slots/{$slot->id}/start", [
            'device_type' => 'mobile',
            'device_user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)',
            'selfie' => $this->getTestFile('selfie.jpg'),
        ]);
        $startResponse->assertSessionHas('error', 'Ujian hanya dapat diakses dari laptop atau komputer. HP dan tablet tidak diperbolehkan.');

        $session = ExamSession::where('user_id', User::where('email', 'student@toefl.test')->first()->id)
            ->where('exam_schedule_slot_id', $slot->id)
            ->first();
        $this->assertNull($session);
    }

    public function test_start_requires_selfie_photo(): void
    {
        $type = ExamType::firstOrCreate(['name' => 'TOEFL iBT'], [
            'max_strikes' => 3,
            'is_active' => true,
        ]);

        $bank = QuestionBank::firstOrCreate(
            ['name' => 'Bank Ujian Resmi'],
            ['exam_type_id' => $type->id, 'is_active' => true],
        );

        $skill = Skill::firstOrCreate(
            ['code' => SkillCode::READING->value],
            [
                'name' => 'Reading',
                'order' => 1,
                'is_active' => true,
            ],
        );

        $passage = Passage::create([
            'title' => 'Passage untuk Ujian',
            'content_text' => '<p>Isi passage ujian resmi.</p>',
            'skill_id' => $skill->id,
        ]);

        $question = Question::create([
            'question_bank_id' => $bank->id,
            'passage_id' => $passage->id,
            'skill_id' => $skill->id,
            'question_text' => 'Apa isi passage tersebut?',
            'option_a' => 'Pilihan A',
            'option_b' => 'Pilihan B',
            'option_c' => 'Pilihan C',
            'option_d' => 'Pilihan D',
            'correct_answer' => 'A',
            'order' => 1,
            'status' => 'approved',
        ]);

        $exam = Exam::create([
            'exam_type_id' => $type->id,
            'title' => 'Ujian Resmi untuk Selfie Test',
            'mode' => ExamMode::OFFICIAL->value,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        $section = ExamSection::create([
            'exam_id' => $exam->id,
            'question_bank_id' => $bank->id,
            'skill_id' => $skill->id,
            'title' => 'Reading — Bank Ujian Resmi',
            'order' => 1,
            'total_questions' => 1,
        ]);

        $section->examSectionQuestions()->create([
            'question_id' => $question->id,
            'order' => 1,
        ]);

        $schedule = ExamSchedule::create([
            'exam_id' => $exam->id,
            'title' => 'Periode Selfie Test',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'is_active' => true,
        ]);

        $slot = ExamScheduleSlot::create([
            'exam_schedule_id' => $schedule->id,
            'date' => now()->toDateString(),
            'start_time' => now()->subMinutes(5)->format('H:i:s'),
            'end_time' => now()->addHours(2)->format('H:i:s'),
            'late_tolerance_minutes' => 15,
            'max_participants' => 30,
            'is_active' => true,
        ]);

        $this->loginAsStudent();

        ExamSession::where('user_id', User::where('email', 'student@toefl.test')->first()->id)
            ->where('status', 'in_progress')
            ->delete();

        $startResponse = $this->post("/exam/slots/{$slot->id}/start", [
            'device_type' => 'desktop',
            'device_user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);
        $startResponse->assertSessionHasErrors('selfie');
    }
}
