<?php

namespace Tests\Feature\DataInduk;

use App\Enums\StatusSkemaPenilaian;
use App\Models\AturanPenilaian;
use App\Models\Pengguna;
use App\Models\ProgramTes;
use App\Models\Section;
use App\Models\SkemaPenilaian;
use App\Modules\Penilaian\Services\LayananAturanPenilaian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AturanPenilaianTest extends TestCase
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

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/admin/data-induk/aturan-penilaian')->assertRedirect('/login');
    }

    public function test_peran_tanpa_izin_ditolak(): void
    {
        $this->masukSebagai('peserta');

        $this->get('/admin/data-induk/aturan-penilaian')->assertForbidden();
    }

    public function test_admin_dapat_melihat_dan_menyimpan_aturan_penilaian(): void
    {
        $this->masukSebagai('admin');
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $section = Section::where('program_tes_id', $programTes->id)
            ->where('nama', 'Reading')
            ->firstOrFail();
        $skema = app(LayananAturanPenilaian::class)->buatVersi($programTes);

        $this->get('/admin/data-induk/aturan-penilaian')->assertOk();
        $this->get("/admin/data-induk/aturan-penilaian/program-tes/{$programTes->id}")->assertOk();

        $this->put(
            "/admin/data-induk/aturan-penilaian/skema/{$skema->id}/section/{$section->id}",
            [
                'tabel_konversi' => [0 => 100, 1 => 155, 2 => 200],
                'skor_mentah_maksimum' => 2,
                'skor_skala_minimum' => 100,
                'skor_skala_maksimum' => 200,
            ],
        )->assertRedirect();

        $aturan = AturanPenilaian::where('skema_penilaian_id', $skema->id)
            ->where('section_id', $section->id)
            ->firstOrFail();

        $this->assertSame(2, $aturan->skor_mentah_maksimum);
        $this->assertSame(100, $aturan->skor_skala_minimum);
        $this->assertSame(200, $aturan->skor_skala_maksimum);
        $this->assertEquals([0 => 100, 1 => 155, 2 => 200], $aturan->tabel_konversi);
    }

    public function test_tabel_konversi_harus_lengkap(): void
    {
        $this->masukSebagai('admin');
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $section = Section::where('program_tes_id', $programTes->id)
            ->where('nama', 'Reading')
            ->firstOrFail();
        $skema = app(LayananAturanPenilaian::class)->buatVersi($programTes);

        $this->put(
            "/admin/data-induk/aturan-penilaian/skema/{$skema->id}/section/{$section->id}",
            [
                'tabel_konversi' => [0 => 100],
                'skor_mentah_maksimum' => 2,
                'skor_skala_minimum' => 100,
                'skor_skala_maksimum' => 200,
            ],
        )->assertSessionHasErrors('tabel_konversi');
    }

    public function test_admin_dapat_membuat_dan_mengaktifkan_versi_baru(): void
    {
        $this->masukSebagai('admin');
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $skemaAktif = SkemaPenilaian::where('program_tes_id', $programTes->id)
            ->where('status', StatusSkemaPenilaian::AKTIF)
            ->firstOrFail();

        $this->post("/admin/data-induk/aturan-penilaian/program-tes/{$programTes->id}/versi")
            ->assertRedirect();

        $skemaDraft = SkemaPenilaian::where('program_tes_id', $programTes->id)
            ->where('status', StatusSkemaPenilaian::DRAFT)
            ->firstOrFail();

        $this->assertSame($skemaAktif->versi + 1, $skemaDraft->versi);

        $this->post("/admin/data-induk/aturan-penilaian/skema/{$skemaDraft->id}/aktifkan")
            ->assertRedirect();

        $this->assertSame(StatusSkemaPenilaian::AKTIF, $skemaDraft->fresh()->status);
        $this->assertSame(StatusSkemaPenilaian::ARSIP, $skemaAktif->fresh()->status);
    }

    public function test_versi_aktif_tidak_dapat_diubah(): void
    {
        $this->masukSebagai('admin');
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $section = Section::where('program_tes_id', $programTes->id)
            ->where('nama', 'Reading')
            ->firstOrFail();
        $skema = SkemaPenilaian::where('program_tes_id', $programTes->id)
            ->where('status', StatusSkemaPenilaian::AKTIF)
            ->firstOrFail();

        $this->put(
            "/admin/data-induk/aturan-penilaian/skema/{$skema->id}/section/{$section->id}",
            [
                'tabel_konversi' => [0 => 100, 1 => 150, 2 => 200],
                'skor_mentah_maksimum' => 2,
                'skor_skala_minimum' => 100,
                'skor_skala_maksimum' => 200,
            ],
        )->assertSessionHas('error');
    }
}
