<?php

namespace Tests\Feature\DataInduk;

use App\Models\Fakultas;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FakultasTest extends TestCase
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
        $this->get('/admin/data-induk/fakultas')->assertRedirect('/login');
    }

    public function test_peran_tanpa_izin_ditolak(): void
    {
        $this->masukSebagai('student');

        $this->get('/admin/data-induk/fakultas')->assertForbidden();
    }

    public function test_admin_dapat_mengakses_halaman(): void
    {
        $this->masukSebagai('admin');

        $this->get('/admin/data-induk/fakultas')->assertOk();
    }

    public function test_admin_dapat_mengelola_fakultas(): void
    {
        $this->masukSebagai('admin');

        $this->post('/admin/data-induk/fakultas', [
            'nama' => 'Fakultas Baru',
            'kode' => 'FB',
            'aktif' => true,
        ])->assertRedirect();

        $fakultas = Fakultas::where('nama', 'Fakultas Baru')->firstOrFail();

        $this->assertDatabaseHas('fakultas', [
            'id' => $fakultas->id,
            'kode' => 'FB',
            'aktif' => true,
        ]);

        $this->put("/admin/data-induk/fakultas/{$fakultas->id}", [
            'nama' => 'Fakultas Diperbarui',
            'kode' => 'FD',
            'aktif' => false,
        ])->assertRedirect();

        $this->assertDatabaseHas('fakultas', [
            'id' => $fakultas->id,
            'nama' => 'Fakultas Diperbarui',
            'kode' => 'FD',
            'aktif' => false,
        ]);

        $this->delete("/admin/data-induk/fakultas/{$fakultas->id}")->assertRedirect();

        $this->assertDatabaseMissing('fakultas', ['id' => $fakultas->id]);
    }
}
