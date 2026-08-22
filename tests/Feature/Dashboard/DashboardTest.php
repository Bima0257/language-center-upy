<?php

namespace Tests\Feature\Dashboard;

use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\ExamScheduleSlot;
use App\Models\ExamType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_admin_can_access_dashboard(): void
    {
        $this->loginAs('admin');

        $this->get('/dashboard')->assertOk();
    }

    public function test_instructor_can_access_dashboard(): void
    {
        $this->loginAs('instructor');

        $this->get('/dashboard')->assertOk();
    }

    public function test_student_can_access_dashboard(): void
    {
        $this->loginAs('student');

        $this->get('/dashboard')->assertOk();
    }

    public function test_student_dashboard_with_slot_time_containing_seconds(): void
    {
        $type = ExamType::firstOrCreate(['name' => 'TOEFL iBT'], [
            'max_strikes' => 3,
            'is_active' => true,
        ]);

        $exam = Exam::create([
            'exam_type_id' => $type->id,
            'title' => 'Test Carbon',
            'description' => 'Regression test',
            'mode' => 'tryout',
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        $schedule = ExamSchedule::create([
            'exam_id' => $exam->id,
            'title' => 'Slot Carbon',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'is_active' => true,
        ]);

        ExamScheduleSlot::create([
            'exam_schedule_id' => $schedule->id,
            'date' => now()->toDateString(),
            'start_time' => now()->subMinutes(5)->format('H:i:s'),
            'end_time' => now()->addHours(2)->format('H:i:s'),
            'late_tolerance_minutes' => 15,
            'max_participants' => 30,
            'is_active' => true,
        ]);

        $this->loginAs('student');

        $this->get('/dashboard')->assertOk();
    }
}
