<?php

namespace Tests\Feature\Ujian;

use App\Models\BagianSoal;
use App\Models\BagianUjian;
use App\Models\BankSoal;
use App\Models\JenisTes;
use App\Models\Keterampilan;
use App\Models\Pengguna;
use App\Models\PeriodeUjian;
use App\Models\Soal;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->masukSebagai('student');

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
            ->has('jenisTes')
            ->has('bankSoal'));
    }

    public function test_admin_dapat_memperbarui_ujian_dari_form_daftar(): void
    {
        $this->masukSebagai('admin');

        $ujian = Ujian::whereHas('bagianUjian')->firstOrFail();

        $this->put(route('admin.ujian.perbarui', $ujian), [
            'jenis_tes_id' => $ujian->jenis_tes_id,
            'judul' => 'Try Out TOEFL iBT Diperbarui',
            'deskripsi' => 'Deskripsi ujian yang diperbarui.',
            'mode' => 'tryout',
            'durasi_menit' => 71,
            'aktif' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('ujian', [
            'id' => $ujian->id,
            'judul' => 'Try Out TOEFL iBT Diperbarui',
            'durasi_menit' => 71,
        ]);
    }

    public function test_pembuatan_ujian_membentuk_bagian_per_keterampilan_dan_bank(): void
    {
        $this->masukSebagai('admin');

        $jenisTes = JenisTes::where('nama', 'TOEFL iBT')->first();
        $bankSoal = BankSoal::where('nama', 'Bank Soal Demo')->first();

        $this->post('/admin/ujian', [
            'jenis_tes_id' => $jenisTes->id,
            'bank_soal_id' => $bankSoal->id,
            'judul' => 'Ujian Baru 2026',
            'deskripsi' => 'Deskripsi',
            'mode' => 'tryout',
            'durasi_menit' => 120,
        ])->assertRedirect();

        $ujian = Ujian::where('judul', 'Ujian Baru 2026')->first();

        $this->assertNotNull($ujian);

        $this->assertGreaterThanOrEqual(1, $ujian->bagianUjian->count());

        foreach ($ujian->bagianUjian as $bagianUjian) {
            $this->assertEquals($bankSoal->id, $bagianUjian->bank_soal_id);
            $this->assertEquals(
                $bagianUjian->soalBagianUjian()->count(),
                $bagianUjian->jumlah_soal,
            );

            $soalBank = Soal::where('bank_soal_id', $bankSoal->id)
                ->where('keterampilan_id', $bagianUjian->keterampilan_id)
                ->pluck('id')
                ->sort()
                ->values()
                ->all();
            $soalUjian = $bagianUjian->soalBagianUjian()
                ->orderBy('soal_id')
                ->pluck('soal_id')
                ->all();

            $this->assertSame($soalBank, $soalUjian);
        }
    }

    public function test_bank_soal_yang_dipakai_ujian_tidak_dapat_diubah(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal Demo')->firstOrFail();

        $this->put(route('pustaka-soal.bank-soal.perbarui', $bankSoal->id), [
            'jenis_tes_id' => $bankSoal->jenis_tes_id,
            'nama' => 'Nama Bank Baru',
            'deskripsi' => $bankSoal->deskripsi,
            'aktif' => $bankSoal->aktif,
        ])->assertSessionHasErrors('bank_soal_id');

        $this->assertDatabaseHas('bank_soal', [
            'id' => $bankSoal->id,
            'nama' => 'Bank Soal Demo',
        ]);
    }

    public function test_soal_baru_tidak_dapat_ditambahkan_ke_bank_yang_dipakai_ujian(): void
    {
        $this->masukSebagai('admin');

        $soal = Soal::whereHas('bagianUjian')->firstOrFail();

        $this->post(route('admin.ujian.soal.simpan'), [
            'bank_soal_id' => $soal->bank_soal_id,
            'keterampilan_id' => $soal->keterampilan_id,
            'bagian_soal_id' => $soal->bagian_soal_id,
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

        $soal = Soal::whereHas('bagianUjian')->firstOrFail();

        $this->put(route('pustaka-soal.perbarui', $soal->id), [
            'bank_soal_id' => $soal->bank_soal_id,
            'keterampilan_id' => $soal->keterampilan_id,
            'bagian_soal_id' => $soal->bagian_soal_id,
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

        $bankSoal = BankSoal::where('nama', 'Bank Soal Demo')->firstOrFail();

        $this->delete(route('pustaka-soal.bank-soal.hapus', $bankSoal->id))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('bank_soal', ['id' => $bankSoal->id]);
    }

    public function test_detail_ujian_menyinkronkan_kembali_semua_soal_bank(): void
    {
        $this->masukSebagai('admin');

        $ujian = Ujian::whereHas('bagianUjian')->firstOrFail();
        $bagianUjian = BagianUjian::where('ujian_id', $ujian->id)->firstOrFail();
        $soalId = $bagianUjian->soalBagianUjian()->value('soal_id');
        $this->assertIsString($soalId);

        $bagianUjian->soalBagianUjian()->where('soal_id', $soalId)->delete();
        $bagianUjian->update(['jumlah_soal' => 0]);

        $this->get(route('admin.ujian.detail', $ujian->id))->assertOk();

        $this->assertDatabaseHas('soal_bagian_ujian', [
            'bagian_ujian_id' => $bagianUjian->id,
            'soal_id' => $soalId,
        ]);
        $this->assertDatabaseHas('bagian_ujian', [
            'id' => $bagianUjian->id,
            'jumlah_soal' => Soal::where('bank_soal_id', $bagianUjian->bank_soal_id)
                ->where('keterampilan_id', $bagianUjian->keterampilan_id)
                ->count(),
        ]);
    }

    public function test_admin_dapat_mengatur_nomor_soal_di_dalam_part_yang_sama(): void
    {
        $this->masukSebagai('admin');

        $bagianUjian = BagianUjian::where('judul', 'Reading Section')->firstOrFail();
        $soalIds = $bagianUjian->soalBagianUjian()->orderBy('urutan')->pluck('soal_id')->all();
        $urutanSoal = [];

        foreach (array_reverse($soalIds) as $indeks => $soalId) {
            $urutanSoal[] = [
                'soal_id' => $soalId,
                'nomor' => $indeks + 1,
            ];
        }

        $this->patch(route('admin.ujian.bagian.susunan', [$bagianUjian->ujian_id, $bagianUjian->id]), [
            'urutan_soal' => $urutanSoal,
        ])->assertRedirect();

        $this->assertSame(
            collect($urutanSoal)->pluck('soal_id')->all(),
            $bagianUjian->soalBagianUjian()->orderBy('urutan')->pluck('soal_id')->all(),
        );
        $this->assertSame(count($soalIds), $bagianUjian->soalBagianUjian()->count());
    }

    public function test_admin_tidak_dapat_memindahkan_soal_ke_part_lain(): void
    {
        $this->masukSebagai('admin');

        $bagianUjian = BagianUjian::where('judul', 'Reading Section')->firstOrFail();
        $partIds = BagianSoal::where('bank_soal_id', $bagianUjian->bank_soal_id)
            ->where('keterampilan_id', $bagianUjian->keterampilan_id)
            ->orderBy('urutan')
            ->pluck('id')
            ->all();
        $soalIds = $bagianUjian->soalBagianUjian()->orderBy('urutan')->pluck('soal_id')->all();

        $this->assertGreaterThanOrEqual(2, count($partIds));
        $this->assertGreaterThanOrEqual(2, count($soalIds));

        Soal::whereKey($soalIds[0])->update(['bagian_soal_id' => $partIds[1]]);

        $urutanSoal = $soalIds;
        [$urutanSoal[0], $urutanSoal[1]] = [$urutanSoal[1], $urutanSoal[0]];
        $payload = [];

        foreach ($urutanSoal as $indeks => $soalId) {
            $payload[] = [
                'soal_id' => $soalId,
                'nomor' => $indeks + 1,
            ];
        }

        $this->patch(route('admin.ujian.bagian.susunan', [$bagianUjian->ujian_id, $bagianUjian->id]), [
            'urutan_soal' => $payload,
        ])->assertSessionHasErrors('urutan_soal');

        $this->assertSame(
            $soalIds,
            $bagianUjian->soalBagianUjian()->orderBy('urutan')->pluck('soal_id')->all(),
        );
    }

    public function test_bank_soal_nonaktif_tidak_dapat_dipakai_ujian_baru(): void
    {
        $this->masukSebagai('admin');

        $jenisTes = JenisTes::where('nama', 'TOEFL iBT')->first();
        $bankSoal = BankSoal::create([
            'jenis_tes_id' => $jenisTes->id,
            'nama' => 'Bank Soal Nonaktif',
            'aktif' => false,
        ]);

        $this->post('/admin/ujian', [
            'jenis_tes_id' => $jenisTes->id,
            'bank_soal_id' => $bankSoal->id,
            'judul' => 'Ujian Dengan Bank Nonaktif',
            'mode' => 'tryout',
            'durasi_menit' => 60,
        ])->assertSessionHasErrors('bank_soal_id');

        $this->assertDatabaseMissing('ujian', ['judul' => 'Ujian Dengan Bank Nonaktif']);
    }

    public function test_ujian_tidak_dapat_dipindah_ke_jenis_tes_nonaktif(): void
    {
        $this->masukSebagai('admin');

        $jenisTes = JenisTes::where('nama', 'TOEFL iBT')->first();
        $jenisTesNonaktif = JenisTes::create([
            'kode' => '010',
            'nama' => 'Jenis Tes Nonaktif Untuk Ujian',
            'batas_pelanggaran' => 3,
            'aktif' => false,
        ]);
        $ujian = Ujian::create([
            'jenis_tes_id' => $jenisTes->id,
            'judul' => 'Ujian Yang Tidak Dipindahkan',
            'mode' => 'tryout',
            'durasi_menit' => 60,
            'aktif' => true,
        ]);

        $this->put("/admin/ujian/{$ujian->id}", [
            'jenis_tes_id' => $jenisTesNonaktif->id,
            'judul' => $ujian->judul,
            'mode' => 'tryout',
            'durasi_menit' => 60,
            'aktif' => true,
        ])->assertSessionHasErrors('jenis_tes_id');

        $this->assertDatabaseHas('ujian', [
            'id' => $ujian->id,
            'jenis_tes_id' => $jenisTes->id,
        ]);
    }

    public function test_bagian_ujian_dari_parent_lain_tidak_dapat_diakses(): void
    {
        $this->masukSebagai('admin');

        $bagianUjian = BagianUjian::where('judul', 'Reading Section')->firstOrFail();
        $ujianLain = Ujian::create([
            'jenis_tes_id' => $bagianUjian->ujian->jenis_tes_id,
            'judul' => 'Ujian Parent Lain',
            'mode' => 'tryout',
            'durasi_menit' => 60,
            'aktif' => true,
        ]);

        $this->delete("/admin/ujian/{$ujianLain->id}/bagian/{$bagianUjian->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('bagian_ujian', ['id' => $bagianUjian->id]);
    }

    public function test_admin_dapat_menghapus_ujian_tanpa_periode(): void
    {
        $this->masukSebagai('admin');

        $jenisTes = JenisTes::where('nama', 'TOEFL iBT')->first();

        $ujian = Ujian::create([
            'jenis_tes_id' => $jenisTes->id,
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

        $jenisTes = JenisTes::where('nama', 'TOEFL iBT')->first();

        $ujian = Ujian::create([
            'jenis_tes_id' => $jenisTes->id,
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

    public function test_sinkronisasi_membuat_bagian_keterampilan_yang_belum_ada_dan_menambahkan_soal(): void
    {
        $this->masukSebagai('admin');

        $ujian = Ujian::whereHas('bagianUjian')->first();
        $keterampilanMembaca = Keterampilan::where('nama', 'Reading')->first();

        // Hapus section reading agar mirip ujian yang dibuat sebelum soal reading tersedia
        $ujian->bagianUjian()->where('keterampilan_id', $keterampilanMembaca->id)->delete();
        $this->assertFalse($ujian->fresh()->bagianUjian()->where('keterampilan_id', $keterampilanMembaca->id)->exists());

        $bankSoalId = BagianUjian::where('ujian_id', $ujian->id)->value('bank_soal_id');
        $this->assertIsString($bankSoalId);
        $soalMembaca = Soal::where('bank_soal_id', $bankSoalId)
            ->where('keterampilan_id', $keterampilanMembaca->id)
            ->firstOrFail();

        $this->post("/admin/ujian/{$ujian->id}/sinkronkan-bagian")->assertRedirect();

        $bagianBaru = BagianUjian::where('ujian_id', $ujian->id)
            ->where('keterampilan_id', $keterampilanMembaca->id)
            ->firstOrFail();
        $this->assertDatabaseHas('soal_bagian_ujian', [
            'bagian_ujian_id' => $bagianBaru->id,
            'soal_id' => $soalMembaca->id,
        ]);
    }

    public function test_sinkronisasi_idempoten_saat_semua_bagian_tersedia(): void
    {
        $this->masukSebagai('admin');

        $ujian = Ujian::whereHas('bagianUjian')->first();

        $before = $ujian->bagianUjian()->count();

        $this->post("/admin/ujian/{$ujian->id}/sinkronkan-bagian")->assertRedirect();

        $this->assertEquals($before, $ujian->fresh()->bagianUjian()->count());
    }
}
