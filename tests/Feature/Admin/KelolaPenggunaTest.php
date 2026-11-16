<?php

namespace Tests\Feature\Admin;

use App\Models\Pengguna;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class KelolaPenggunaTest extends TestCase
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

    public function test_daftar_pengguna_tidak_menampilkan_superadmin(): void
    {
        $this->masukSebagai('superadmin');

        $jumlahAdmin = Pengguna::query()
            ->role('admin')
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'superadmin'))
            ->count();

        $this->get(route('admin.identity.users.index'))->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Identity/User/Index')
            ->has('users.data', $jumlahAdmin)
            ->where('userCounts.admin', $jumlahAdmin));
    }

    public function test_tab_peserta_menyaring_pengguna_sesuai_role(): void
    {
        $this->masukSebagai('superadmin');

        $jumlahPeserta = Pengguna::query()->role('peserta')->count();

        $this->get(route('admin.identity.users.index', ['tab' => 'peserta']))->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Identity/User/Index')
            ->has('users.data', $jumlahPeserta));
    }

    public function test_tab_custom_menampilkan_pengguna_dengan_role_custom(): void
    {
        $this->masukSebagai('superadmin');

        $roleCustom = Role::create(['name' => 'operator', 'guard_name' => 'web']);
        $mahasiswa = Pengguna::where('email', 'peserta@toefl.test')->firstOrFail();
        $mahasiswa->syncRoles([$roleCustom]);

        $this->get(route('admin.identity.users.index', ['tab' => 'custom']))->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Identity/User/Index')
            ->has('users.data', 1)
            ->where('users.data.0.email', 'peserta@toefl.test'));
    }

    public function test_daftar_pengguna_mendukung_q_per_page_sort_dan_filter_status(): void
    {
        $this->masukSebagai('superadmin');

        $page = $this->get('/admin/identity/users?tab=peserta&q=peserta@toefl.test&f_status=true&per_page=50&sort=name&dir=asc')
            ->assertOk()
            ->inertiaPage();

        $this->assertSame(50, $page['props']['users']['per_page']);
        $this->assertTrue(collect($page['props']['users']['data'])->contains(
            fn (array $pengguna): bool => $pengguna['email'] === 'peserta@toefl.test',
        ));
        $this->assertTrue(collect($page['props']['users']['data'])->every(
            fn (array $pengguna): bool => (bool) $pengguna['status'] === true,
        ));
    }

    public function test_admin_biasa_tidak_dapat_mengakses_identity(): void
    {
        $this->masukSebagai('admin');

        $this->get(route('admin.identity.users.index'))->assertForbidden();
    }

    public function test_superadmin_dapat_mengubah_password_pengguna(): void
    {
        $this->masukSebagai('superadmin');
        $mahasiswa = Pengguna::where('email', 'peserta@toefl.test')->firstOrFail();

        $this->put(route('admin.identity.users.update', $mahasiswa), [
            'password' => 'Sandi-baru-123',
            'password_confirmation' => 'Sandi-baru-123',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('Sandi-baru-123', $mahasiswa->fresh()->password));
    }

    public function test_konfirmasi_password_harus_cocok(): void
    {
        $this->masukSebagai('superadmin');
        $mahasiswa = Pengguna::where('email', 'peserta@toefl.test')->firstOrFail();

        $this->put(route('admin.identity.users.update', $mahasiswa), [
            'password' => 'Sandi-baru-123',
            'password_confirmation' => 'sandi-lain-456',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $mahasiswa->fresh()->password));
    }
}
