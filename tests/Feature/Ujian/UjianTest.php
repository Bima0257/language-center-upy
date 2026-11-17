<?php

namespace Tests\Feature\Ujian;

use App\Models\BankSoal;
use App\Models\PartSoal;
use App\Models\PartUjian;
use App\Models\Pengguna;
use App\Models\PeriodeUjian;
use App\Models\ProgramTes;
use App\Models\Section;
use App\Models\Soal;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UjianTest extends TestCase
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
        $this->get('/admin/ujian')->assertRedirect('/login');
    }

    public function test_peran_tanpa_izin_ditolak(): void
    {
        $this->masukSebagai('peserta');

        $this->get('/admin/ujian')->assertForbidden();
    }

    public function test_admin_dapat_mengakses_halaman(): void
    {
        $this->masukSebagai('admin');

        $this->get('/admin/ujian')->assertOk();
    }

    public function test_halaman_daftar_menyediakan_data_form_tambah_ujian(): void
    {
        $this->masukSebagai('admin');

        $this->get('/admin/ujian')->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Ujian/index')
            ->has('programTes')
            ->has('bankSoal'));
    }

    public function test_halaman_daftar_menampilkan_grouping_semua_program_tes_aktif(): void
    {
        $this->masukSebagai('admin');

        $this->get('/admin/ujian')->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Ujian/index')
            ->where('programTesTerpilihId', null)
            ->has('ringkasanProgramTes', ProgramTes::where('aktif', true)->count())
            ->has('ujian', 0));
    }

    public function test_halaman_daftar_memfilter_ujian_berdasarkan_program_tes(): void
    {
        $this->masukSebagai('admin');

        $programTes = ProgramTes::where('aktif', true)
            ->whereHas('ujian')
            ->firstOrFail();
        $jumlahUjian = Ujian::where('program_tes_id', $programTes->id)->count();

        $this->get('/admin/ujian?program_tes_id='.$programTes->id)->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Ujian/index')
            ->where('programTesTerpilihId', $programTes->id)
            ->where('programTesTerpilih.id', $programTes->id)
            ->has('ujian', $jumlahUjian)
            ->where('ujian', function (Collection $ujian) use ($programTes): bool {
                return $ujian->every(
                    fn (array $item): bool => $item['program_tes_id'] === $programTes->id,
                );
            }));
    }

    public function test_admin_dapat_memperbarui_ujian_dari_form_daftar(): void
    {
        $this->masukSebagai('admin');

        $ujian = Ujian::whereHas('partUjian')->firstOrFail();

        $this->put(route('admin.ujian.perbarui', $ujian), [
            'program_tes_id' => $ujian->program_tes_id,
            'judul' => 'Try Out EPT Diperbarui',
            'deskripsi' => 'Deskripsi ujian yang diperbarui.',
            'mode' => 'tryout',
            'durasi_menit' => 71,
            'aktif' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('ujian', [
            'id' => $ujian->id,
            'judul' => 'Try Out EPT Diperbarui',
            'durasi_menit' => 71,
        ]);
    }

    public function test_deskripsi_ujian_diperbarui_sebagai_html_tersanitasi(): void
    {
        $this->masukSebagai('admin');

        $ujian = Ujian::whereHas('partUjian')->firstOrFail();

        $this->put(route('admin.ujian.perbarui', $ujian), [
            'program_tes_id' => $ujian->program_tes_id,
            'judul' => $ujian->judul,
            'deskripsi' => '<p><em>Deskripsi baru</em></p><script>alert(1)</script>',
            'mode' => $ujian->mode->value,
            'durasi_menit' => $ujian->durasi_menit,
            'aktif' => true,
        ])->assertRedirect();

        $ujian->refresh();

        $this->assertStringContainsString('<em>Deskripsi baru</em>', (string) $ujian->deskripsi);
        $this->assertStringNotContainsString('<script', (string) $ujian->deskripsi);
    }

    public function test_pembuatan_ujian_membentuk_part_per_section_dan_bank(): void
    {
        $this->masukSebagai('admin');

        $programTes = ProgramTes::where('nama', 'EPT')->first();
        $bankSoal = BankSoal::where('nama', 'Bank Soal EPT')->first();

        $this->post('/admin/ujian', [
            'program_tes_id' => $programTes->id,
            'bank_soal_id' => $bankSoal->id,
            'judul' => 'Ujian Baru 2026',
            'deskripsi' => 'Deskripsi',
            'mode' => 'tryout',
            'durasi_menit' => 120,
        ])->assertRedirect();

        $ujian = Ujian::where('judul', 'Ujian Baru 2026')->first();

        $this->assertNotNull($ujian);

        $this->assertGreaterThanOrEqual(1, $ujian->partUjian->count());

        foreach ($ujian->partUjian as $partUjian) {
            $this->assertEquals($bankSoal->id, $partUjian->bank_soal_id);
            $this->assertEquals(
                $partUjian->soalPartUjian()->count(),
                $partUjian->jumlah_soal,
            );

            $soalBank = Soal::where('bank_soal_id', $bankSoal->id)
                ->where('section_id', $partUjian->section_id)
                ->pluck('id')
                ->sort()
                ->values()
                ->all();
            $soalUjian = $partUjian->soalPartUjian()
                ->orderBy('soal_id')
                ->pluck('soal_id')
                ->all();

            $this->assertSame($soalBank, $soalUjian);
        }
    }

    public function test_deskripsi_ujian_disimpan_sebagai_html_tersanitasi(): void
    {
        $this->masukSebagai('admin');

        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $bankSoal = BankSoal::where('nama', 'Bank Soal EPT')->firstOrFail();

        $this->post('/admin/ujian', [
            'program_tes_id' => $programTes->id,
            'bank_soal_id' => $bankSoal->id,
            'judul' => 'Ujian Deskripsi HTML',
            'deskripsi' => '<p>Deskripsi <strong>ujian</strong></p><script>alert(1)</script>',
            'mode' => 'tryout',
            'durasi_menit' => 120,
        ])->assertRedirect();

        $deskripsi = Ujian::where('judul', 'Ujian Deskripsi HTML')->value('deskripsi');

        $this->assertIsString($deskripsi);
        $this->assertStringContainsString('<strong>ujian</strong>', $deskripsi);
        $this->assertStringNotContainsString('<script', $deskripsi);
    }

    public function test_bank_soal_yang_dipakai_ujian_tidak_dapat_diubah(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal EPT')->firstOrFail();

        $this->put(route('pustaka-soal.bank-soal.perbarui', $bankSoal->id), [
            'program_tes_id' => $bankSoal->program_tes_id,
            'nama' => 'Nama Bank Baru',
            'deskripsi' => $bankSoal->deskripsi,
            'aktif' => $bankSoal->aktif,
        ])->assertSessionHasErrors('bank_soal_id');

        $this->assertDatabaseHas('bank_soal', [
            'id' => $bankSoal->id,
            'nama' => 'Bank Soal EPT',
        ]);
    }

    public function test_soal_baru_tidak_dapat_ditambahkan_ke_bank_yang_dipakai_ujian(): void
    {
        $this->masukSebagai('admin');

        $soal = Soal::whereHas('partUjian')->firstOrFail();

        $this->post(route('admin.ujian.soal.simpan'), [
            'bank_soal_id' => $soal->bank_soal_id,
            'section_id' => $soal->section_id,
            'part_soal_id' => $soal->part_soal_id,
            'teks_soal' => 'Soal baru yang seharusnya ditolak',
            'pilihan_a' => 'A',
            'pilihan_b' => 'B',
            'pilihan_c' => 'C',
            'pilihan_d' => 'D',
            'kunci_jawaban' => 'A',
            'urutan' => 99,
        ])->assertSessionHasErrors('bank_soal_id');
    }

    public function test_soal_yang_dipakai_ujian_tidak_dapat_diubah_atau_dihapus(): void
    {
        $this->masukSebagai('admin');

        $soal = Soal::whereHas('partUjian')->firstOrFail();

        $this->put(route('pustaka-soal.perbarui', $soal->id), [
            'bank_soal_id' => $soal->bank_soal_id,
            'section_id' => $soal->section_id,
            'part_soal_id' => $soal->part_soal_id,
            'materi_soal_id' => $soal->materi_soal_id,
            'teks_soal' => $soal->teks_soal,
            'pilihan_a' => $soal->pilihan_a,
            'pilihan_b' => $soal->pilihan_b,
            'pilihan_c' => $soal->pilihan_c,
            'pilihan_d' => $soal->pilihan_d,
            'kunci_jawaban' => $soal->kunci_jawaban,
        ])->assertSessionHasErrors('bank_soal_id');

        $this->delete(route('pustaka-soal.hapus', $soal->id))
            ->assertSessionHasErrors('bank_soal_id');

        $this->assertDatabaseHas('soal', ['id' => $soal->id]);
    }

    public function test_bank_soal_yang_dipakai_ujian_tidak_dapat_dihapus(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal EPT')->firstOrFail();

        $this->delete(route('pustaka-soal.bank-soal.hapus', $bankSoal->id))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('bank_soal', ['id' => $bankSoal->id]);
    }

    public function test_detail_ujian_menyinkronkan_kembali_semua_soal_bank(): void
    {
        $this->masukSebagai('admin');

        $ujian = Ujian::whereHas('partUjian')->firstOrFail();
        $ujian->update(['aktif' => false]);
        $partUjian = PartUjian::where('ujian_id', $ujian->id)->firstOrFail();
        $soalId = $partUjian->soalPartUjian()->value('soal_id');
        $this->assertIsString($soalId);

        $partUjian->soalPartUjian()->where('soal_id', $soalId)->delete();
        $partUjian->update(['jumlah_soal' => 0]);

        $this->get(route('admin.ujian.detail', $ujian->id))->assertOk();

        $this->assertDatabaseHas('soal_part_ujian', [
            'part_ujian_id' => $partUjian->id,
            'soal_id' => $soalId,
        ]);
        $this->assertDatabaseHas('part_ujian', [
            'id' => $partUjian->id,
            'jumlah_soal' => Soal::where('bank_soal_id', $partUjian->bank_soal_id)
                ->where('section_id', $partUjian->section_id)
                ->count(),
        ]);
    }

    public function test_admin_dapat_mengatur_nomor_soal_di_dalam_part_yang_sama(): void
    {
        $this->masukSebagai('admin');

        $partUjian = PartUjian::where('judul', 'Reading Section')->firstOrFail();
        $barisSoal = $partUjian->soalPartUjian()->with('soal')->orderBy('urutan')->get();
        $soalIds = $barisSoal->pluck('soal_id')->all();
        $urutanSoal = [];
        $nomor = 1;

        foreach ($barisSoal->groupBy(fn ($baris): string => (string) ($baris->soal?->part_soal_id ?? 'none')) as $kelompok) {
            foreach ($kelompok->reverse() as $baris) {
                $urutanSoal[] = [
                    'soal_id' => $baris->soal_id,
                    'nomor' => $nomor++,
                ];
            }
        }

        $this->patch(route('admin.ujian.part.susunan', [$partUjian->ujian_id, $partUjian->id]), [
            'urutan_soal' => $urutanSoal,
        ])->assertRedirect();

        $this->assertSame(
            collect($urutanSoal)->pluck('soal_id')->all(),
            $partUjian->soalPartUjian()->orderBy('urutan')->pluck('soal_id')->all(),
        );
        $this->assertSame(count($soalIds), $partUjian->soalPartUjian()->count());
    }

    public function test_admin_tidak_dapat_memindahkan_soal_ke_part_lain(): void
    {
        $this->masukSebagai('admin');

        $partUjian = PartUjian::where('judul', 'Reading Section')->firstOrFail();
        $partIds = PartSoal::where('bank_soal_id', $partUjian->bank_soal_id)
            ->where('section_id', $partUjian->section_id)
            ->orderBy('urutan')
            ->pluck('id')
            ->all();
        $soalIds = $partUjian->soalPartUjian()->orderBy('urutan')->pluck('soal_id')->all();

        $this->assertGreaterThanOrEqual(2, count($partIds));
        $this->assertGreaterThanOrEqual(2, count($soalIds));

        Soal::whereKey($soalIds[0])->update(['part_soal_id' => $partIds[1]]);

        $urutanSoal = $soalIds;
        [$urutanSoal[0], $urutanSoal[1]] = [$urutanSoal[1], $urutanSoal[0]];
        $payload = [];

        foreach ($urutanSoal as $indeks => $soalId) {
            $payload[] = [
                'soal_id' => $soalId,
                'nomor' => $indeks + 1,
            ];
        }

        $this->patch(route('admin.ujian.part.susunan', [$partUjian->ujian_id, $partUjian->id]), [
            'urutan_soal' => $payload,
        ])->assertSessionHasErrors('urutan_soal');

        $this->assertSame(
            $soalIds,
            $partUjian->soalPartUjian()->orderBy('urutan')->pluck('soal_id')->all(),
        );
    }

    public function test_bank_soal_nonaktif_tidak_dapat_dipakai_ujian_baru(): void
    {
        $this->masukSebagai('admin');

        $programTes = ProgramTes::where('nama', 'EPT')->first();
        $bankSoal = BankSoal::create([
            'program_tes_id' => $programTes->id,
            'nama' => 'Bank Soal Nonaktif',
            'aktif' => false,
        ]);

        $this->post('/admin/ujian', [
            'program_tes_id' => $programTes->id,
            'bank_soal_id' => $bankSoal->id,
            'judul' => 'Ujian Dengan Bank Nonaktif',
            'mode' => 'tryout',
            'durasi_menit' => 60,
        ])->assertSessionHasErrors('bank_soal_id');

        $this->assertDatabaseMissing('ujian', ['judul' => 'Ujian Dengan Bank Nonaktif']);
    }

    public function test_ujian_tidak_dapat_dipindah_ke_program_tes_nonaktif(): void
    {
        $this->masukSebagai('admin');

        $programTes = ProgramTes::where('nama', 'EPT')->first();
        $programTesNonaktif = ProgramTes::create([
            'kode' => '010',
            'nama' => 'Program Tes Nonaktif Untuk Ujian',
            'batas_pelanggaran' => 3,
            'aktif' => false,
        ]);
        $ujian = Ujian::create([
            'program_tes_id' => $programTes->id,
            'judul' => 'Ujian Yang Tidak Dipindahkan',
            'mode' => 'tryout',
            'durasi_menit' => 60,
            'aktif' => true,
        ]);

        $this->put("/admin/ujian/{$ujian->id}", [
            'program_tes_id' => $programTesNonaktif->id,
            'judul' => $ujian->judul,
            'mode' => 'tryout',
            'durasi_menit' => 60,
            'aktif' => true,
        ])->assertSessionHasErrors('program_tes_id');

        $this->assertDatabaseHas('ujian', [
            'id' => $ujian->id,
            'program_tes_id' => $programTes->id,
        ]);
    }

    public function test_part_ujian_dari_parent_lain_tidak_dapat_diakses(): void
    {
        $this->masukSebagai('admin');

        $partUjian = PartUjian::where('judul', 'Reading Section')->firstOrFail();
        $ujianLain = Ujian::create([
            'program_tes_id' => $partUjian->ujian->program_tes_id,
            'judul' => 'Ujian Parent Lain',
            'mode' => 'tryout',
            'durasi_menit' => 60,
            'aktif' => true,
        ]);

        $this->delete("/admin/ujian/{$ujianLain->id}/part/{$partUjian->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('part_ujian', ['id' => $partUjian->id]);
    }

    public function test_admin_dapat_menghapus_ujian_tanpa_periode(): void
    {
        $this->masukSebagai('admin');

        $programTes = ProgramTes::where('nama', 'EPT')->first();

        $ujian = Ujian::create([
            'program_tes_id' => $programTes->id,
            'judul' => 'Ujian Tanpa Jadwal',
            'mode' => 'tryout',
            'durasi_menit' => 60,
            'aktif' => true,
        ]);

        $this->delete("/admin/ujian/{$ujian->id}")->assertRedirect();

        $this->assertDatabaseMissing('ujian', ['id' => $ujian->id]);
    }

    public function test_admin_tidak_dapat_menghapus_ujian_dengan_periode(): void
    {
        $this->masukSebagai('admin');

        $programTes = ProgramTes::where('nama', 'EPT')->first();

        $ujian = Ujian::create([
            'program_tes_id' => $programTes->id,
            'judul' => 'Ujian Terjadwal',
            'mode' => 'tryout',
            'durasi_menit' => 60,
            'aktif' => true,
        ]);

        PeriodeUjian::create([
            'ujian_id' => $ujian->id,
            'judul' => 'Periode Demo',
            'tanggal_mulai' => now()->subDay(),
            'tanggal_selesai' => now()->addDay(),
            'aktif' => true,
        ]);

        $this->delete("/admin/ujian/{$ujian->id}")->assertRedirect();

        $this->assertDatabaseHas('ujian', ['id' => $ujian->id]);
    }

    public function test_sinkronisasi_membuat_part_section_yang_belum_ada_dan_menambahkan_soal(): void
    {
        $this->masukSebagai('admin');

        $ujian = Ujian::whereHas('partUjian')->first();
        $ujian->update(['aktif' => false]);
        $sectionMembaca = Section::where('nama', 'Reading')->first();

        // Hapus section reading agar mirip ujian yang dibuat sebelum soal reading tersedia
        $ujian->partUjian()->where('section_id', $sectionMembaca->id)->delete();
        $this->assertFalse($ujian->fresh()->partUjian()->where('section_id', $sectionMembaca->id)->exists());

        $bankSoalId = PartUjian::where('ujian_id', $ujian->id)->value('bank_soal_id');
        $this->assertIsString($bankSoalId);
        $soalMembaca = Soal::where('bank_soal_id', $bankSoalId)
            ->where('section_id', $sectionMembaca->id)
            ->firstOrFail();

        $this->post("/admin/ujian/{$ujian->id}/sinkronkan-part")->assertRedirect();

        $partBaru = PartUjian::where('ujian_id', $ujian->id)
            ->where('section_id', $sectionMembaca->id)
            ->firstOrFail();
        $this->assertDatabaseHas('soal_part_ujian', [
            'part_ujian_id' => $partBaru->id,
            'soal_id' => $soalMembaca->id,
        ]);
    }

    public function test_sinkronisasi_idempoten_saat_semua_part_tersedia(): void
    {
        $this->masukSebagai('admin');

        $ujian = Ujian::whereHas('partUjian')->first();
        $ujian->update(['aktif' => false]);

        $before = $ujian->partUjian()->count();

        $this->post("/admin/ujian/{$ujian->id}/sinkronkan-part")->assertRedirect();

        $this->assertEquals($before, $ujian->fresh()->partUjian()->count());
    }

    public function test_sinkronisasi_ujian_aktif_ditolak(): void
    {
        $this->masukSebagai('admin');

        $ujian = Ujian::whereHas('partUjian')->where('aktif', true)->firstOrFail();

        $this->post(route('admin.ujian.sinkronkan-part', $ujian))
            ->assertRedirect()
            ->assertSessionHasErrors('ujian');
    }
}
