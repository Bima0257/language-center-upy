<?php

namespace Tests\Feature\Admin;

use App\Models\Pengguna;
use App\Models\Permission;
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

    public function test_tiga_peran_bawaan_disemai(): void
    {
        $this->assertSame(
            ['admin', 'peserta', 'superadmin'],
            Role::query()->orderBy('name')->pluck('name')->all(),
        );
    }

    public function test_admin_tidak_dapat_mengangkat_pengguna_ke_superadmin(): void
    {
        $this->masukSebagai('admin');
        $mahasiswa = Pengguna::where('email', 'peserta@toefl.test')->firstOrFail();
        $superadmin = Role::findByName('superadmin');

        $this->put(route('admin.identity.users.update', $mahasiswa), [
            'role_ids' => [$superadmin->id],
        ])->assertForbidden();

        $this->assertTrue($mahasiswa->fresh()->hasRole('peserta'));
    }

    public function test_superadmin_dapat_memberikan_peran_admin(): void
    {
        $this->masukSebagai('superadmin');
        $mahasiswa = Pengguna::where('email', 'peserta@toefl.test')->firstOrFail();
        $adminRole = Role::findByName('admin');

        $this->put(route('admin.identity.users.update', $mahasiswa), [
            'role_ids' => [$adminRole->id],
        ])->assertRedirect();

        $this->assertTrue($mahasiswa->fresh()->hasRole('admin'));
    }

    public function test_superadmin_melewati_middleware_route_permission(): void
    {
        $this->masukSebagai('superadmin');

        Permission::query()->where('name', 'admin.ujian.daftar')->delete();

        $this->get('/admin/ujian')->assertOk();
    }
}
