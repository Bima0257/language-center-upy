<?php

namespace Tests\Feature\Admin;

use App\Models\Pengguna;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeranPenggunaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function masukSebagai(string $peran): Pengguna
    {
        $pengguna = Pengguna::where('email', $peran.'@toefl.test')->firstOrFail();

        $this->post('/login', [
            'email' => $pengguna->email,
            'password' => 'password',
        ]);

        return $pengguna;
    }

    public function test_lima_peran_bawaan_disemai(): void
    {
        $this->assertSame(
            ['admin', 'instructor', 'proctor', 'student', 'superadmin'],
            Role::query()->orderBy('name')->pluck('name')->all(),
        );
    }

    public function test_admin_tidak_dapat_mengangkat_pengguna_ke_superadmin(): void
    {
        $this->masukSebagai('admin');
        $mahasiswa = Pengguna::where('email', 'student@toefl.test')->firstOrFail();

        $this->patch(route('admin.pengguna.perbarui', $mahasiswa), [
            'role' => 'superadmin',
        ])->assertForbidden();

        $this->assertTrue($mahasiswa->fresh()->hasRole('student'));
    }

    public function test_superadmin_dapat_memberikan_peran_admin(): void
    {
        $this->masukSebagai('superadmin');
        $mahasiswa = Pengguna::where('email', 'student@toefl.test')->firstOrFail();

        $this->patch(route('admin.pengguna.perbarui', $mahasiswa), [
            'role' => 'admin',
        ])->assertRedirect();

        $this->assertTrue($mahasiswa->fresh()->hasRole('admin'));
    }

    public function test_superadmin_melewati_middleware_permission(): void
    {
        $this->masukSebagai('superadmin');
        $peranSuperadmin = Role::findByName('superadmin');
        $peranSuperadmin->revokePermissionTo('exam.manage');

        $this->get('/admin/ujian')->assertOk();
    }
}
