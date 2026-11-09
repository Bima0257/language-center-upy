<?php

namespace Tests\Feature\Ujian;

use App\Models\BankSoal;
use App\Models\Pengguna;
use App\Models\Soal;
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

    private function masukSebagaiAdmin(): void
    {
        $pengguna = Pengguna::where('email', 'admin@toefl.test')->firstOrFail();

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

    public function test_admin_mendapatkan_preview_dari_bank_soal_dan_tanpa_kunci_jawaban(): void
    {
        $this->masukSebagaiAdmin();

        $bankSoal = BankSoal::where('nama', 'Bank Soal Demo')->firstOrFail();

        $page = $this->get(
            '/preview/exam-take?bank_soal_id='.$bankSoal->id.'&jenis_tes_id='.$bankSoal->jenis_tes_id,
        )->assertOk()->inertiaPage();

        $this->assertSame('Ujian/PratinjauPengerjaan/index', $page['component']);
        $this->assertSame($bankSoal->nama, $page['props']['overview']['judulUjian']);
        $this->assertNotEmpty($page['props']['blocks']);
        $this->assertArrayNotHasKey('kunci_jawaban', $page['props']['blocks'][0]['soal'][0]);
        $this->assertSame(
            route('pustaka-soal.daftar', ['bank_soal_id' => $bankSoal->id]),
            $page['props']['manageUrl'],
        );
    }

    public function test_admin_membuka_soal_terpilih_langsung_di_preview_bank(): void
    {
        $this->masukSebagaiAdmin();

        $bankSoal = BankSoal::where('nama', 'Bank Soal Demo')->firstOrFail();
        $soal = Soal::where('bank_soal_id', $bankSoal->id)
            ->orderBy('urutan')
            ->skip(1)
            ->firstOrFail();

        $page = $this->get(
            '/preview/exam-take?bank_soal_id='.$bankSoal->id
                .'&jenis_tes_id='.$bankSoal->jenis_tes_id
                .'&soal_id='.$soal->id,
        )->assertOk()->inertiaPage();

        $this->assertSame($soal->id, $page['props']['initialSoalId']);
    }

    public function test_route_preview_soal_lama_sudah_dihapus(): void
    {
        $this->masukSebagaiAdmin();

        $soal = Soal::whereNotNull('bank_soal_id')->firstOrFail();

        $this->get('/pustaka-soal/soal/'.$soal->id.'/pratinjau')
            ->assertNotFound();
    }

    public function test_mahasiswa_ditolak_membuka_preview_bank_soal(): void
    {
        $this->masukSebagaiMahasiswa();

        $bankSoal = BankSoal::where('nama', 'Bank Soal Demo')->firstOrFail();

        $this->get('/preview/exam-take?bank_soal_id='.$bankSoal->id)
            ->assertForbidden();
    }
}
