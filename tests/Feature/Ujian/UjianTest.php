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
            $this->assertNotNull($bagianUjian->bank_soal_id);
            $this->assertEquals($bankSoal->id, $bagianUjian->bank_soal_id);
        }
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

    public function test_admin_dapat_menambahkan_soal_ke_bagian(): void
    {
        $this->masukSebagai('admin');

        $bagianUjian = BagianUjian::where('judul', 'Reading Section')->first();
        $bankSoal = BankSoal::find($bagianUjian->bank_soal_id);

        $soal = Soal::where('bank_soal_id', $bankSoal->id)
            ->where('keterampilan_id', $bagianUjian->keterampilan_id)
            ->firstOrFail();

        $this->post("/admin/ujian/{$bagianUjian->ujian_id}/bagian/{$bagianUjian->id}/soal", [
            'soal_ids' => [$soal->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('soal_bagian_ujian', [
            'bagian_ujian_id' => $bagianUjian->id,
            'soal_id' => $soal->id,
        ]);
    }

    public function test_soal_dari_bank_lain_tidak_dapat_ditambahkan(): void
    {
        $this->masukSebagai('admin');

        $bagianUjian = BagianUjian::where('judul', 'Reading Section')->first();
        $bankSoalLain = BankSoal::where('nama', 'Bank Soal 2022')->first();
        $keterampilan = Keterampilan::where('nama', 'Reading')->first();

        $soalBankLain = Soal::create([
            'bank_soal_id' => $bankSoalLain->id,
            'keterampilan_id' => $keterampilan->id,
            'bagian_soal_id' => BagianSoal::where('bank_soal_id', $bankSoalLain->id)->where('keterampilan_id', $keterampilan->id)->first()->id,
            'teks_soal' => 'Soal dari bank lain',
            'pilihan_a' => 'A',
            'pilihan_b' => 'B',
            'pilihan_c' => 'C',
            'pilihan_d' => 'D',
            'kunci_jawaban' => 'A',
            'urutan' => 1,
        ]);

        $this->post("/admin/ujian/{$bagianUjian->ujian_id}/bagian/{$bagianUjian->id}/soal", [
            'soal_ids' => [$soalBankLain->id],
        ])->assertRedirect();

        $this->assertDatabaseMissing('soal_bagian_ujian', [
            'bagian_ujian_id' => $bagianUjian->id,
            'soal_id' => $soalBankLain->id,
        ]);
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

        $bankSoalId = $ujian->bagianUjian()->first()->bank_soal_id;
        $soalMembaca = Soal::where('bank_soal_id', $bankSoalId)
            ->where('keterampilan_id', $keterampilanMembaca->id)
            ->firstOrFail();

        $this->post("/admin/ujian/{$ujian->id}/sinkronkan-bagian")->assertRedirect();

        $bagianBaru = $ujian->fresh()->bagianUjian()->where('keterampilan_id', $keterampilanMembaca->id)->first();
        $this->assertNotNull($bagianBaru);
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
