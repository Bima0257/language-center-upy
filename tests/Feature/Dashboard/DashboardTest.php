<?php

namespace Tests\Feature\Dashboard;

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
}
