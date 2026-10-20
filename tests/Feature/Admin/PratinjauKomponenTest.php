<?php

namespace Tests\Feature\Admin;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PratinjauKomponenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function masukSebagai(string $peran): Pengguna
    {
        $pengguna = Pengguna::where('email', $peran.'@toefl.test')->first();

        $this->post('/login', [
            'email' => $pengguna->email,
            'password' => 'password',
        ]);

        return $pengguna;
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/admin/komponen')->assertRedirect('/login');
    }

    public function test_non_admin_tidak_dapat_mengakses_pratinjau_komponen(): void
    {
        $this->masukSebagai('student');

        $this->get('/admin/komponen')->assertForbidden();
    }

    public function test_admin_dapat_mengakses_pratinjau_komponen(): void
    {
        $this->masukSebagai('admin');

        $this->get('/admin/komponen')->assertOk();
    }
}
