<?php

namespace Tests\Feature\Schedule;

use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\ExamScheduleSlot;
use App\Models\ExamSession;
use App\Models\ExamType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulesTest extends TestCase
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

    private function exam(): Exam
    {
        return Exam::first();
    }

    private function scheduleData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Gelombang Baru',
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(9)->format('Y-m-d'),
        ], $overrides);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/schedules')->assertRedirect('/login');
    }

    public function test_wrong_role_is_forbidden(): void
    {
        $this->loginAs('student');

        $this->get('/admin/schedules')->assertForbidden();
    }

    public function test_admin_can_access_all_schedules_page(): void
    {
        $this->loginAs('admin');

        $this->get('/admin/schedules')->assertOk();
    }

    public function test_admin_can_access_create_page(): void
    {
        $this->loginAs('admin');

        $this->get("/admin/exams/{$this->exam()->id}/schedules/create")->assertOk();
    }

    public function test_admin_can_create_period(): void
    {
        $this->loginAs('admin');

        $this->post("/admin/exams/{$this->exam()->id}/schedules", $this->scheduleData())
            ->assertRedirect();

        $this->assertDatabaseHas('exam_schedules', [
            'exam_id' => $this->exam()->id,
            'title' => 'Gelombang Baru',
        ]);
    }

    public function test_overlapping_period_is_rejected(): void
    {
        $this->loginAs('admin');

        $existing = ExamSchedule::first();

        $this->post("/admin/exams/{$this->exam()->id}/schedules", $this->scheduleData([
            'start_date' => $existing->start_date->format('Y-m-d'),
            'end_date' => $existing->end_date->format('Y-m-d'),
        ]))->assertRedirect();

        $this->assertDatabaseMissing('exam_schedules', [
            'exam_id' => $this->exam()->id,
            'title' => 'Gelombang Baru',
        ]);
    }

    public function test_admin_can_access_period_page(): void
    {
        $this->loginAs('admin');

        $schedule = ExamSchedule::first();

        $this->get("/admin/schedules/{$schedule->id}")->assertOk();
    }

    public function test_admin_can_create_slot(): void
    {
        $this->loginAs('admin');

        $schedule = ExamSchedule::first();

        $this->post("/admin/schedules/{$schedule->id}/slots", [
            'date' => $schedule->start_date->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'late_tolerance_minutes' => 15,
            'max_participants' => 25,
        ])->assertRedirect();

        $this->assertDatabaseHas('exam_schedule_slots', [
            'exam_schedule_id' => $schedule->id,
            'start_time' => '09:00',
        ]);
    }

    public function test_slot_date_outside_period_is_rejected(): void
    {
        $this->loginAs('admin');

        $schedule = ExamSchedule::first();

        $this->post("/admin/schedules/{$schedule->id}/slots", [
            'date' => now()->addMonths(3)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '11:00',
        ])->assertSessionHasErrors('date');

        $this->assertDatabaseCount('exam_schedule_slots', 3);
    }

    public function test_overlapping_slot_is_rejected(): void
    {
        $this->loginAs('admin');

        $schedule = ExamSchedule::first();
        $existing = ExamScheduleSlot::where('exam_schedule_id', $schedule->id)->first();

        $this->post("/admin/schedules/{$schedule->id}/slots", [
            'date' => $existing->date->format('Y-m-d'),
            'start_time' => substr((string) $existing->start_time, 0, 5),
            'end_time' => substr((string) $existing->end_time, 0, 5),
            'max_participants' => 30,
        ])->assertRedirect();

        $this->assertDatabaseCount('exam_schedule_slots', 3);
    }

    public function test_admin_cannot_delete_period_with_slots(): void
    {
        $this->loginAs('admin');

        $schedule = ExamSchedule::first();

        $this->delete("/admin/schedules/{$schedule->id}")->assertRedirect();

        $this->assertDatabaseHas('exam_schedules', ['id' => $schedule->id]);
    }

    public function test_admin_can_delete_period_without_slots(): void
    {
        $this->loginAs('admin');

        $schedule = ExamSchedule::create([
            'exam_id' => $this->exam()->id,
            'title' => 'Periode Kosong',
            'start_date' => now()->addDays(20),
            'end_date' => now()->addDays(21),
            'is_active' => true,
        ]);

        $this->delete("/admin/schedules/{$schedule->id}")->assertRedirect();

        $this->assertDatabaseMissing('exam_schedules', ['id' => $schedule->id]);
    }

    public function test_admin_cannot_delete_slot_with_participants(): void
    {
        $this->loginAs('admin');

        $slot = ExamScheduleSlot::whereHas('sessions')->first();
        $this->assertNotNull($slot);

        $this->delete("/admin/schedules/slots/{$slot->id}")->assertRedirect();

        $this->assertDatabaseHas('exam_schedule_slots', ['id' => $slot->id]);
    }

    public function test_admin_can_delete_empty_slot(): void
    {
        $this->loginAs('admin');

        $schedule = ExamSchedule::first();

        $slot = ExamScheduleSlot::create([
            'exam_schedule_id' => $schedule->id,
            'date' => $schedule->start_date,
            'start_time' => '12:00',
            'end_time' => '14:00',
            'max_participants' => 30,
            'is_active' => true,
        ]);

        $this->delete("/admin/schedules/slots/{$slot->id}")->assertRedirect();

        $this->assertDatabaseMissing('exam_schedule_slots', ['id' => $slot->id]);
    }

    public function test_student_cannot_join_same_period_twice(): void
    {
        $student = $this->loginAs('student');

        $examType = ExamType::where('name', 'TOEFL iBT')->first();
        $exam = $this->exam();

        $schedule = ExamSchedule::create([
            'exam_id' => $exam->id,
            'title' => 'Periode Percobaan',
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(11),
            'is_active' => true,
        ]);

        $slot = ExamScheduleSlot::create([
            'exam_schedule_id' => $schedule->id,
            'date' => now()->addDays(10)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'max_participants' => 30,
            'is_active' => true,
        ]);

        ExamSession::create([
            'exam_schedule_id' => $schedule->id,
            'exam_schedule_slot_id' => $slot->id,
            'user_id' => $student->id,
            'status' => 'submitted',
            'started_at' => now(),
            'submitted_at' => now()->addMinutes(30),
        ]);

        $this->post("/exam/slots/{$slot->id}/start")->assertRedirect();

        $this->assertDatabaseCount('exam_sessions', 4);
    }
}
