<?php

namespace Tests\Feature\DataInduk;

use App\Models\KebijakanMateriSoal;
use App\Models\Pengguna;
use App\Models\ProgramTes;
use App\Models\Section;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionTest extends TestCase
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
        $this->get('/admin/data-induk/section')->assertRedirect('/login');
    }

    public function test_peran_tanpa_izin_ditolak(): void
    {
        $this->masukSebagai('peserta');

        $this->get('/admin/data-induk/section')->assertForbidden();
    }

    public function test_admin_dapat_melihat_ringkasan_dan_detail_section_per_program_tes(): void
    {
        $this->masukSebagai('admin');
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();

        $this->get('/admin/data-induk/section')->assertOk();
        $this->get("/admin/data-induk/section/program-tes/{$programTes->id}")->assertOk();
    }

    public function test_admin_dapat_menyimpan_urutan_section(): void
    {
        $this->masukSebagai('admin');
        Ujian::query()->update(['aktif' => false]);
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $section = Section::where('program_tes_id', $programTes->id)
            ->orderBy('urutan')
            ->get();

        $this->assertGreaterThan(1, $section->count());

        $reversed = $section->pluck('id')->reverse()->values()->all();

        $this->post('/admin/data-induk/section/urutkan', [
            'section' => $reversed,
            'program_tes_id' => $programTes->id,
        ])->assertRedirect();

        $updated = Section::where('program_tes_id', $programTes->id)
            ->orderBy('urutan')
            ->pluck('id')
            ->all();

        $this->assertSame($reversed, $updated);
    }

    public function test_reorder_menolak_daftar_section_yang_tidak_lengkap(): void
    {
        $this->masukSebagai('admin');
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $section = Section::where('program_tes_id', $programTes->id)
            ->orderBy('urutan')
            ->get();
        $urutanAwal = $section->sortBy('id')->pluck('urutan', 'id')->all();

        $this->post('/admin/data-induk/section/urutkan', [
            'section' => $section->pluck('id')->take(1)->all(),
            'program_tes_id' => $programTes->id,
        ])->assertSessionHasErrors('section');

        $this->assertSame(
            $urutanAwal,
            Section::where('program_tes_id', $programTes->id)
                ->orderBy('id')
                ->pluck('urutan', 'id')
                ->all(),
        );
    }

    public function test_reorder_menolak_section_dari_program_tes_lain(): void
    {
        $this->masukSebagai('admin');
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $programTesLain = ProgramTes::create([
            'kode' => '002',
            'nama' => 'IELTS',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);
        $sectionLain = Section::create([
            'program_tes_id' => $programTesLain->id,
            'kode' => '002001',
            'nama' => 'Reading',
            'urutan' => 1,
            'aktif' => true,
        ]);
        $ids = Section::where('program_tes_id', $programTes->id)
            ->orderBy('urutan')
            ->pluck('id')
            ->all();
        $ids[0] = $sectionLain->id;

        $this->post('/admin/data-induk/section/urutkan', [
            'section' => $ids,
            'program_tes_id' => $programTes->id,
        ])->assertSessionHasErrors('section');
    }

    public function test_admin_dapat_membuat_section_dengan_kode_otomatis(): void
    {
        $this->masukSebagai('admin');
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();

        $this->post('/admin/data-induk/section', [
            'program_tes_id' => $programTes->id,
            'nama' => 'Speaking',
            'durasi_menit' => 30,
            'aktif' => true,
        ])->assertRedirect();

        $section = Section::where('program_tes_id', $programTes->id)
            ->where('nama', 'Speaking')
            ->firstOrFail();

        $this->assertSame('001003', $section->kode);
    }

    public function test_admin_dapat_menyimpan_policy_materi_per_section(): void
    {
        $this->masukSebagai('admin');
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();

        $this->post('/admin/data-induk/section', [
            'program_tes_id' => $programTes->id,
            'nama' => 'Speaking Audio',
            'durasi_menit' => 30,
            'aktif' => true,
            'ketentuan_materi' => 'required',
            'aturan_media' => [
                'text' => 'forbidden',
                'audio' => 'required',
                'image' => 'optional',
            ],
        ])->assertRedirect();

        $section = Section::where('nama', 'Speaking Audio')->firstOrFail();
        $kebijakan = KebijakanMateriSoal::where('section_id', $section->id)->firstOrFail();

        $this->assertSame('required', $kebijakan->ketentuan_materi->value);
        $this->assertDatabaseHas('aturan_media_materi_soal', [
            'kebijakan_materi_soal_id' => $kebijakan->id,
            'jenis_media' => 'audio',
            'ketentuan' => 'required',
        ]);
    }

    public function test_policy_materi_kontradiktif_ditolak(): void
    {
        $this->masukSebagai('admin');
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();

        $this->post('/admin/data-induk/section', [
            'program_tes_id' => $programTes->id,
            'nama' => 'Policy Kontradiktif',
            'durasi_menit' => 30,
            'aktif' => true,
            'ketentuan_materi' => 'optional',
            'aturan_media' => [
                'text' => 'optional',
                'audio' => 'required',
                'image' => 'forbidden',
            ],
        ])->assertSessionHasErrors('ketentuan_materi');

        $this->assertDatabaseMissing('section', ['nama' => 'Policy Kontradiktif']);
    }

    public function test_policy_materi_dapat_dikosongkan_jika_section_belum_dipakai_soal(): void
    {
        $this->masukSebagai('admin');
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();

        $this->post('/admin/data-induk/section', [
            'program_tes_id' => $programTes->id,
            'nama' => 'Policy Yang Dikosongkan',
            'durasi_menit' => 30,
            'aktif' => true,
            'ketentuan_materi' => 'required',
            'aturan_media' => [
                'text' => 'required',
                'audio' => 'forbidden',
                'image' => 'optional',
            ],
        ])->assertRedirect();

        $section = Section::where('nama', 'Policy Yang Dikosongkan')->firstOrFail();

        $this->put("/admin/data-induk/section/{$section->id}", [
            'nama' => $section->nama,
            'deskripsi' => null,
            'durasi_menit' => 30,
            'aktif' => true,
            'ketentuan_materi' => null,
            'aturan_media' => [
                'text' => null,
                'audio' => null,
                'image' => null,
            ],
        ])->assertRedirect();

        $this->assertDatabaseMissing('kebijakan_materi_soal', [
            'section_id' => $section->id,
        ]);
    }

    public function test_section_dengan_policy_tidak_terpakai_dapat_dihapus_beserta_policynya(): void
    {
        $this->masukSebagai('admin');
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();

        $this->post('/admin/data-induk/section', [
            'program_tes_id' => $programTes->id,
            'nama' => 'Section Dengan Policy',
            'durasi_menit' => 30,
            'aktif' => true,
            'ketentuan_materi' => 'required',
            'aturan_media' => [
                'text' => 'required',
                'audio' => 'forbidden',
                'image' => 'optional',
            ],
        ])->assertRedirect();

        $section = Section::where('nama', 'Section Dengan Policy')->firstOrFail();

        $this->delete("/admin/data-induk/section/{$section->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('section', ['id' => $section->id]);
        $this->assertDatabaseMissing('kebijakan_materi_soal', ['section_id' => $section->id]);
        $this->assertDatabaseHas('activity_log', [
            'description' => 'section_deleted',
            'subject_id' => $section->id,
        ]);
    }

    public function test_nama_section_duplikat_dalam_program_tes_ditolak(): void
    {
        $this->masukSebagai('admin');
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();

        $this->post('/admin/data-induk/section', [
            'program_tes_id' => $programTes->id,
            'nama' => 'Reading',
            'durasi_menit' => 30,
            'aktif' => true,
        ])->assertSessionHasErrors('nama');
    }

    public function test_urutan_section_duplikat_ditolak(): void
    {
        $this->masukSebagai('admin');
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $section = Section::where('program_tes_id', $programTes->id)
            ->orderBy('urutan')
            ->get();
        $target = $section->last();

        $this->put("/admin/data-induk/section/{$target->id}", [
            'nama' => $target->nama,
            'urutan' => $section->first()->urutan,
            'durasi_menit' => 30,
            'aktif' => $target->aktif,
        ])->assertSessionHasErrors('urutan');
    }

    public function test_nama_section_sama_diperbolehkan_pada_program_tes_lain(): void
    {
        $this->masukSebagai('admin');
        $programTes = ProgramTes::create([
            'kode' => '002',
            'nama' => 'IELTS',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);

        $this->post('/admin/data-induk/section', [
            'program_tes_id' => $programTes->id,
            'nama' => 'Reading',
            'durasi_menit' => 30,
            'aktif' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('section', [
            'program_tes_id' => $programTes->id,
            'kode' => '002001',
            'nama' => 'Reading',
        ]);
    }

    public function test_kode_section_tidak_dapat_diubah(): void
    {
        $this->masukSebagai('admin');
        Ujian::query()->update(['aktif' => false]);
        $section = Section::where('nama', 'Reading')->firstOrFail();
        $kodeAwal = $section->kode;

        $this->put("/admin/data-induk/section/{$section->id}", [
            'nama' => 'Reading Updated',
            'durasi_menit' => 30,
            'aktif' => true,
        ])->assertRedirect()->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('section', [
            'id' => $section->id,
            'kode' => $kodeAwal,
            'nama' => 'Reading Updated',
        ]);
    }

    public function test_kode_tidak_digunakan_kembali_setelah_section_dihapus(): void
    {
        $this->masukSebagai('admin');
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();

        $this->post('/admin/data-induk/section', [
            'program_tes_id' => $programTes->id,
            'nama' => 'Speaking',
            'durasi_menit' => 30,
            'aktif' => true,
        ])->assertRedirect();

        $section = Section::where('nama', 'Speaking')->firstOrFail();
        $kodeDihapus = $section->kode;

        $this->delete("/admin/data-induk/section/{$section->id}")->assertRedirect();

        $this->post('/admin/data-induk/section', [
            'program_tes_id' => $programTes->id,
            'nama' => 'Writing',
            'durasi_menit' => 30,
            'aktif' => true,
        ])->assertRedirect();

        $this->assertNotSame($kodeDihapus, Section::where('nama', 'Writing')->firstOrFail()->kode);
    }

    public function test_section_dipakai_ujian_aktif_tidak_dapat_diperbarui(): void
    {
        $this->masukSebagai('admin');
        $section = Section::where('nama', 'Reading')->firstOrFail();

        $this->put("/admin/data-induk/section/{$section->id}", [
            'nama' => 'Reading Baru',
            'durasi_menit' => 30,
            'aktif' => true,
        ])->assertSessionHasErrors('nama');

        $this->assertDatabaseHas('section', [
            'id' => $section->id,
            'nama' => 'Reading',
        ]);
    }

    public function test_urutan_section_tidak_dapat_diubah_saat_ujian_aktif(): void
    {
        $this->masukSebagai('admin');
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $ids = Section::where('program_tes_id', $programTes->id)
            ->orderBy('urutan')
            ->pluck('id')
            ->all();

        $this->post('/admin/data-induk/section/urutkan', [
            'section' => array_reverse($ids),
            'program_tes_id' => $programTes->id,
        ])->assertSessionHasErrors('section');
    }

    public function test_section_dapat_diperbarui_setelah_ujian_dinonaktifkan(): void
    {
        $this->masukSebagai('admin');
        Ujian::query()->update(['aktif' => false]);
        $section = Section::where('nama', 'Reading')->firstOrFail();

        $this->put("/admin/data-induk/section/{$section->id}", [
            'nama' => 'Reading Setelah Nonaktif',
            'durasi_menit' => 30,
            'aktif' => true,
        ])->assertRedirect()->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('section', [
            'id' => $section->id,
            'nama' => 'Reading Setelah Nonaktif',
        ]);
    }
}
