<?php

namespace Tests\Feature\Ujian;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PratinjauPengerjaanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function masukSebagaiMahasiswa(): void
    {
        $pengguna = Pengguna::where('email', 'student@toefl.test')->firstOrFail();

        $this->post('/login', [
            'email' => $pengguna->email,
            'password' => 'password',
        ]);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/preview/exam-take')->assertRedirect('/login');
    }

    public function test_mahasiswa_mendapatkan_preview_dengan_soal_mandiri(): void
    {
        $this->masukSebagaiMahasiswa();

        $this->get('/preview/exam-take?soal=10')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Ujian/PratinjauPengerjaan/index')
                ->has('blocks', 5)
                ->where('blocks.3.soal.1.materiSoal', null));
    }
}
