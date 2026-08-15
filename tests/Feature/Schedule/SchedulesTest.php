<?php

namespace Tests\Feature\Schedule;

use App\Models\Exam;
use App\Models\ExamSchedule;
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

        $exam = Exam::first();

        $this->get("/admin/exams/{$exam->id}/schedules/create")->assertOk();
    }

    public function test_admin_can_create_schedule(): void
    {
        $this->loginAs('admin');

        $exam = Exam::first();

        $this->post("/admin/exams/{$exam->id}/schedules", [
            'title' => 'Sesi Baru',
            'scheduled_start' => now()->addDays(3)->format('Y-m-d H:i'),
            'scheduled_end' => now()->addDays(3)->addHours(2)->format('Y-m-d H:i'),
            'late_tolerance_minutes' => 15,
            'max_participants' => 30,
        ])->assertRedirect();

        $this->assertDatabaseHas('exam_schedules', [
            'exam_id' => $exam->id,
            'title' => 'Sesi Baru',
        ]);
    }

    public function test_overlapping_schedule_is_rejected(): void
    {
        $this->loginAs('admin');

        $exam = Exam::first();
        $existing = ExamSchedule::where('exam_id', $exam->id)->first();

        $this->post("/admin/exams/{$exam->id}/schedules", [
            'title' => 'Sesi Bentrok',
            'scheduled_start' => $existing->scheduled_start->format('Y-m-d H:i'),
            'scheduled_end' => $existing->scheduled_end->format('Y-m-d H:i'),
            'late_tolerance_minutes' => 15,
            'max_participants' => 30,
        ])->assertRedirect();

        $this->assertDatabaseMissing('exam_schedules', [
            'exam_id' => $exam->id,
            'title' => 'Sesi Bentrok',
        ]);
    }

    public function test_admin_can_delete_schedule(): void
    {
        $this->loginAs('admin');

        $schedule = ExamSchedule::first();

        $this->delete("/admin/exams/{$schedule->exam_id}/schedules/{$schedule->id}")->assertRedirect();

        $this->assertDatabaseMissing('exam_schedules', ['id' => $schedule->id]);
    }
}
