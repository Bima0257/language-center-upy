<?php

namespace Tests\Feature\Ujian;

use App\Models\BankSoal;
use App\Models\PartUjian;
use App\Models\Pengguna;
use App\Models\Soal;
use App\Models\SoalPartUjian;
use App\Models\Ujian;
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
        $pengguna = Pengguna::where('email', 'peserta@toefl.test')->firstOrFail();

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

        $bankSoal = BankSoal::where('nama', 'Bank Soal EPT')->firstOrFail();

        $page = $this->get(
            '/preview/exam-take?bank_soal_id='.$bankSoal->id.'&program_tes_id='.$bankSoal->program_tes_id,
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

    public function test_admin_mendapatkan_preview_dari_susunan_ujian_aktual(): void
    {
        $this->masukSebagaiAdmin();

        $ujian = Ujian::where('judul', 'EPT Try Out 1')->firstOrFail();
        $partUjian = PartUjian::where('ujian_id', $ujian->id)->orderBy('urutan')->firstOrFail();
        $barisSoal = SoalPartUjian::where('part_ujian_id', $partUjian->id)->orderBy('urutan')->get();
        $soalPertama = $barisSoal->firstOrFail();
        $soalKedua = $barisSoal->get(1);

        if ($soalKedua !== null) {
            $urutanPertama = $soalPertama->urutan;
            $urutanKedua = $soalKedua->urutan;

            $soalPertama->update(['urutan' => 999]);
            $soalKedua->update(['urutan' => $urutanPertama]);
            $soalPertama->update(['urutan' => $urutanKedua]);
        }

        $page = $this->get(route('admin.ujian.pratinjau', $ujian))->assertOk()->inertiaPage();
        $expectedQuestionIds = PartUjian::where('ujian_id', $ujian->id)
            ->orderBy('urutan')
            ->get()
            ->flatMap(fn (PartUjian $section) => $section->soalPartUjian()->orderBy('urutan')->pluck('soal_id'))
            ->values()
            ->all();
        $actualQuestionIds = collect($page['props']['blocks'])
            ->flatMap(fn (array $block) => collect($block['soal'])->pluck('id'))
            ->values()
            ->all();

        $this->assertSame('Ujian/PratinjauPengerjaan/index', $page['component']);
        $this->assertSame($ujian->judul, $page['props']['overview']['judulUjian']);
        $this->assertSame(count($expectedQuestionIds), $page['props']['overview']['jumlahSoal']);
        $this->assertSame($expectedQuestionIds, $actualQuestionIds);
        $this->assertArrayNotHasKey('kunci_jawaban', $page['props']['blocks'][0]['soal'][0]);
        $this->assertNotNull($page['props']['blocks'][0]['partSoal']['id']);
        $this->assertSame(
            5,
            collect($page['props']['blocks'])
                ->pluck('partSoal.id')
                ->unique()
                ->count(),
        );

        $overviewParts = collect($page['props']['overview']['section'])
            ->flatMap(fn (array $section) => collect($section['partSoal']))
            ->values();
        $this->assertSame(5, $overviewParts->count());

        $listeningOverview = collect($page['props']['overview']['section'])
            ->firstWhere('nama', 'Listening');
        $this->assertNotNull($listeningOverview);
        $listeningPartSatu = collect($listeningOverview['partSoal'])
            ->firstWhere('nama', 'Listening Part 1');
        $this->assertNotNull($listeningPartSatu);
        $this->assertSame(20, $listeningPartSatu['jumlahSoal']);

        $this->assertSame(route('admin.ujian.detail', $ujian), $page['props']['backUrl']);
        $this->assertSame(route('admin.ujian.detail', $ujian), $page['props']['manageUrl']);
    }

    public function test_tamu_diarahkan_ke_login_saat_membuka_preview_ujian(): void
    {
        $ujian = Ujian::where('judul', 'EPT Try Out 1')->firstOrFail();

        $this->get(route('admin.ujian.pratinjau', $ujian))->assertRedirect('/login');
    }

    public function test_mahasiswa_ditolak_membuka_preview_ujian(): void
    {
        $this->masukSebagaiMahasiswa();

        $ujian = Ujian::where('judul', 'EPT Try Out 1')->firstOrFail();

        $this->get(route('admin.ujian.pratinjau', $ujian))->assertForbidden();
    }

    public function test_admin_membuka_soal_terpilih_langsung_di_preview_bank(): void
    {
        $this->masukSebagaiAdmin();

        $bankSoal = BankSoal::where('nama', 'Bank Soal EPT')->firstOrFail();
        $soal = Soal::where('bank_soal_id', $bankSoal->id)
            ->orderBy('urutan')
            ->skip(1)
            ->firstOrFail();

        $page = $this->get(
            '/preview/exam-take?bank_soal_id='.$bankSoal->id
                .'&program_tes_id='.$bankSoal->program_tes_id
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

        $bankSoal = BankSoal::where('nama', 'Bank Soal EPT')->firstOrFail();

        $this->get('/preview/exam-take?bank_soal_id='.$bankSoal->id)
            ->assertForbidden();
    }
}
