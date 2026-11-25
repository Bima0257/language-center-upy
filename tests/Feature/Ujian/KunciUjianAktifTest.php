<?php

namespace Tests\Feature\Ujian;

use App\Models\Pengguna;
use App\Models\ProgramTes;
use App\Models\Section;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KunciUjianAktifTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function masukSebagaiAdmin(): void
    {
        $pengguna = Pengguna::where('email', 'admin@toefl.test')->firstOrFail();

        $this->post('/login', [
            'email' => $pengguna->email,
            'password' => 'password',
        ])->assertRedirect();
    }

    public function test_halaman_program_tes_menghitung_ujian_aktif(): void
    {
        $this->masukSebagaiAdmin();
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();

        $halaman = $this->get('/admin/data-induk/program-tes')->assertOk()->inertiaPage();
        $baris = collect($halaman['props']['programTes'])->firstWhere('id', $programTes->id);

        $this->assertNotNull($baris);
        $this->assertGreaterThanOrEqual(1, $baris['ujian_aktif_count']);
    }

    public function test_halaman_section_menandai_section_dipakai_ujian_aktif(): void
    {
        $this->masukSebagaiAdmin();
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();

        $halaman = $this->get("/admin/data-induk/section/program-tes/{$programTes->id}")
            ->assertOk()
            ->inertiaPage();

        $this->assertTrue($halaman['props']['memilikiUjianAktif']);

        $sectionMembaca = collect($halaman['props']['section'])->firstWhere('nama', 'Reading');

        $this->assertNotNull($sectionMembaca);
        $this->assertTrue($sectionMembaca['dipakai_ujian_aktif']);
    }

    public function test_section_baru_tetap_dapat_diperbarui_saat_program_tes_punya_ujian_aktif(): void
    {
        $this->masukSebagaiAdmin();
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();

        $this->post('/admin/data-induk/section', [
            'program_tes_id' => $programTes->id,
            'nama' => 'Section Bebas',
            'durasi_menit' => 30,
            'aktif' => true,
        ])->assertRedirect();

        $section = Section::where('nama', 'Section Bebas')->firstOrFail();

        $this->put("/admin/data-induk/section/{$section->id}", [
            'nama' => 'Section Bebas Diperbarui',
            'durasi_menit' => 30,
            'aktif' => true,
        ])->assertRedirect()->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('section', [
            'id' => $section->id,
            'nama' => 'Section Bebas Diperbarui',
        ]);
    }

    public function test_section_dipakai_ujian_aktif_ditolak_dan_diizinkan_setelah_nonaktif(): void
    {
        $this->masukSebagaiAdmin();
        $section = Section::where('nama', 'Reading')->firstOrFail();

        $this->put("/admin/data-induk/section/{$section->id}", [
            'nama' => 'Reading Ditolak',
            'durasi_menit' => 30,
            'aktif' => true,
        ])->assertSessionHasErrors('nama');

        Ujian::query()->update(['aktif' => false]);

        $this->put("/admin/data-induk/section/{$section->id}", [
            'nama' => 'Reading Diizinkan',
            'durasi_menit' => 30,
            'aktif' => true,
        ])->assertRedirect()->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('section', [
            'id' => $section->id,
            'nama' => 'Reading Diizinkan',
        ]);
    }
}
