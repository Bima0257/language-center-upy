<?php

namespace Tests\Feature\Exam;

use App\Enums\ExamMode;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\ExamScheduleSlot;
use App\Models\ExamType;
use App\Models\User;
use App\Modules\Schedule\Repositories\Contracts\SlotRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
