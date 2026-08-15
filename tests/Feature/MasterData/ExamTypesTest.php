<?php

namespace Tests\Feature\MasterData;

use App\Models\ExamType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamTypesTest extends TestCase
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
        $this->get('/admin/master-data/exam-types')->assertRedirect('/login');
    }

    public function test_wrong_role_is_forbidden(): void
    {
        $this->loginAs('student');

        $this->get('/admin/master-data/exam-types')->assertForbidden();
    }

    public function test_admin_can_access_page(): void
    {
        $this->loginAs('admin');

        $this->get('/admin/master-data/exam-types')->assertOk();
    }

    public function test_admin_can_create_exam_type(): void
    {
        $this->loginAs('admin');

        $this->post('/admin/master-data/exam-types', [
            'name' => 'TOEFL Junior',
            'max_strikes' => 3,
            'description' => 'Tes untuk junior',
        ])->assertRedirect();

        $this->assertDatabaseHas('exam_types', ['name' => 'TOEFL Junior']);
    }

    public function test_duplicate_exam_type_name_is_rejected(): void
    {
        $this->loginAs('admin');

        $this->post('/admin/master-data/exam-types', [
            'name' => 'TOEFL iBT',
            'max_strikes' => 3,
        ])->assertSessionHasErrors('name');
    }

    public function test_admin_can_delete_exam_type(): void
    {
        $this->loginAs('admin');

        $examType = ExamType::where('name', 'TOEFL iBT')->first();

        $this->delete("/admin/master-data/exam-types/{$examType->id}")->assertRedirect();

        $this->assertDatabaseMissing('exam_types', ['id' => $examType->id]);
    }
}
