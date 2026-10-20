<?php

namespace Tests\Feature\DataInduk;

use App\Models\AturanPenilaian;
use App\Models\BankSoal;
use App\Models\JenisTes;
use App\Models\Pengguna;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JenisTesTest extends TestCase
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
        $this->get('/admin/data-induk/jenis-tes')->assertRedirect('/login');
    }

    public function test_peran_tanpa_izin_ditolak(): void
    {
        $this->masukSebagai('student');

        $this->get('/admin/data-induk/jenis-tes')->assertForbidden();
    }

    public function test_admin_dapat_mengakses_halaman(): void
    {
        $this->masukSebagai('admin');

        $this->get('/admin/data-induk/jenis-tes')->assertOk();
    }

    public function test_admin_dapat_membuat_jenis_tes(): void
    {
        $this->masukSebagai('admin');

        $this->post('/admin/data-induk/jenis-tes', [
            'nama' => 'TOEFL Junior',
            'batas_pelanggaran' => 3,
            'deskripsi' => 'Tes untuk junior',
        ])->assertRedirect();

        $this->assertDatabaseHas('jenis_tes', ['nama' => 'TOEFL Junior']);
        $this->assertDatabaseHas('jenis_tes', ['nama' => 'TOEFL Junior', 'kode' => '002']);
    }

    public function test_kode_jenis_tes_tidak_digunakan_kembali_setelah_dihapus(): void
    {
        $this->masukSebagai('admin');

        $this->post('/admin/data-induk/jenis-tes', [
            'nama' => 'Jenis Sementara',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ])->assertRedirect();

        $jenisTes = JenisTes::where('nama', 'Jenis Sementara')->firstOrFail();
        $this->assertSame('002', $jenisTes->kode);

        $this->delete("/admin/data-induk/jenis-tes/{$jenisTes->id}")->assertRedirect();

        $this->post('/admin/data-induk/jenis-tes', [
            'nama' => 'Jenis Berikutnya',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('jenis_tes', [
            'nama' => 'Jenis Berikutnya',
            'kode' => '003',
        ]);
    }

    public function test_nama_jenis_tes_duplikat_ditolak(): void
    {
        $this->masukSebagai('admin');

        $this->post('/admin/data-induk/jenis-tes', [
            'nama' => 'TOEFL iBT',
            'batas_pelanggaran' => 3,
        ])->assertSessionHasErrors('nama');
    }

    public function test_admin_dapat_menghapus_jenis_tes_belum_dipakai(): void
    {
        $this->masukSebagai('admin');

        $jenisTes = JenisTes::create([
            'kode' => '010',
            'nama' => 'TOEFL Junior',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);

        $this->delete("/admin/data-induk/jenis-tes/{$jenisTes->id}")->assertRedirect();

        $this->assertDatabaseMissing('jenis_tes', ['id' => $jenisTes->id]);
    }

    public function test_jenis_tes_dipakai_bank_soal_tidak_bisa_dihapus(): void
    {
        $this->masukSebagai('admin');

        $jenisTes = JenisTes::where('nama', 'TOEFL iBT')->first();

        $this->delete("/admin/data-induk/jenis-tes/{$jenisTes->id}")->assertRedirect();

        $this->assertDatabaseHas('jenis_tes', ['id' => $jenisTes->id]);
        $this->assertTrue(session()->has('error'));
    }

    public function test_jenis_tes_dengan_aturan_penilaian_tidak_dapat_dihapus(): void
    {
        $this->masukSebagai('admin');

        $jenisTes = JenisTes::create([
            'kode' => '011',
            'nama' => 'Jenis Dengan Scoring',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);

        AturanPenilaian::create([
            'jenis_tes_id' => $jenisTes->id,
            'kode_keterampilan' => '011001',
            'tabel_konversi' => ['0' => 0],
            'skor_mentah_maksimum' => 1,
            'skor_skala_maksimum' => 1,
        ]);

        $this->delete("/admin/data-induk/jenis-tes/{$jenisTes->id}")
            ->assertRedirect();

        $this->assertDatabaseHas('jenis_tes', ['id' => $jenisTes->id]);
        $this->assertDatabaseHas('aturan_penilaian', ['jenis_tes_id' => $jenisTes->id]);
    }

    public function test_jenis_tes_nonaktif_tidak_dapat_dipakai_bank_soal_baru(): void
    {
        $this->masukSebagai('admin');

        $jenisTes = JenisTes::create([
            'kode' => '012',
            'nama' => 'Jenis Nonaktif',
            'batas_pelanggaran' => 3,
            'aktif' => false,
        ]);

        $this->post('/pustaka-soal/bank-soal', [
            'jenis_tes_id' => $jenisTes->id,
            'nama' => 'Bank Nonaktif',
        ])->assertSessionHasErrors('jenis_tes_id');

        $this->assertDatabaseMissing('bank_soal', ['nama' => 'Bank Nonaktif']);
    }

    public function test_ujian_menolak_bank_soal_dari_jenis_tes_lain(): void
    {
        $this->masukSebagai('admin');

        $jenisTes = JenisTes::create([
            'kode' => '013',
            'nama' => 'Jenis Lain',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);
        $bankSoal = BankSoal::where('nama', 'Bank Soal 2022')->first();

        $this->post('/admin/ujian', [
            'jenis_tes_id' => $jenisTes->id,
            'bank_soal_id' => $bankSoal->id,
            'judul' => 'Ujian Dengan Bank Tidak Sesuai',
            'mode' => 'tryout',
            'durasi_menit' => 60,
        ])->assertSessionHasErrors('bank_soal_id');

        $this->assertDatabaseMissing('ujian', ['judul' => 'Ujian Dengan Bank Tidak Sesuai']);
    }

    public function test_nama_jenis_tes_unik_pada_database(): void
    {
        JenisTes::create([
            'kode' => '014',
            'nama' => 'Nama Unik Database',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);

        $this->expectException(QueryException::class);

        JenisTes::create([
            'kode' => '015',
            'nama' => 'Nama Unik Database',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);
    }
}
