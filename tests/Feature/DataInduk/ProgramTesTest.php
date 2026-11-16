<?php

namespace Tests\Feature\DataInduk;

use App\Models\AturanPenilaian;
use App\Models\BankSoal;
use App\Models\Pengguna;
use App\Models\ProgramTes;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramTesTest extends TestCase
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
        $this->get('/admin/data-induk/program-tes')->assertRedirect('/login');
    }

    public function test_peran_tanpa_izin_ditolak(): void
    {
        $this->masukSebagai('peserta');

        $this->get('/admin/data-induk/program-tes')->assertForbidden();
    }

    public function test_admin_dapat_mengakses_halaman(): void
    {
        $this->masukSebagai('admin');

        $this->get('/admin/data-induk/program-tes')->assertOk();
    }

    public function test_admin_dapat_membuat_program_tes(): void
    {
        $this->masukSebagai('admin');

        $this->post('/admin/data-induk/program-tes', [
            'nama' => 'TOEFL Junior',
            'batas_pelanggaran' => 3,
            'deskripsi' => 'Tes untuk junior',
        ])->assertRedirect();

        $this->assertDatabaseHas('program_tes', ['nama' => 'TOEFL Junior']);
        $this->assertDatabaseHas('program_tes', ['nama' => 'TOEFL Junior', 'kode' => '002']);
    }

    public function test_kode_program_tes_tidak_digunakan_kembali_setelah_dihapus(): void
    {
        $this->masukSebagai('admin');

        $this->post('/admin/data-induk/program-tes', [
            'nama' => 'Jenis Sementara',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ])->assertRedirect();

        $programTes = ProgramTes::where('nama', 'Jenis Sementara')->firstOrFail();
        $this->assertSame('002', $programTes->kode);

        $this->delete("/admin/data-induk/program-tes/{$programTes->id}")->assertRedirect();

        $this->post('/admin/data-induk/program-tes', [
            'nama' => 'Jenis Berikutnya',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('program_tes', [
            'nama' => 'Jenis Berikutnya',
            'kode' => '003',
        ]);
    }

    public function test_nama_program_tes_duplikat_ditolak(): void
    {
        $this->masukSebagai('admin');

        $this->post('/admin/data-induk/program-tes', [
            'nama' => 'EPT',
            'batas_pelanggaran' => 3,
        ])->assertSessionHasErrors('nama');
    }

    public function test_admin_dapat_menghapus_program_tes_belum_dipakai(): void
    {
        $this->masukSebagai('admin');

        $programTes = ProgramTes::create([
            'kode' => '010',
            'nama' => 'TOEFL Junior',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);

        $this->delete("/admin/data-induk/program-tes/{$programTes->id}")->assertRedirect();

        $this->assertDatabaseMissing('program_tes', ['id' => $programTes->id]);
    }

    public function test_program_tes_dipakai_bank_soal_tidak_bisa_dihapus(): void
    {
        $this->masukSebagai('admin');

        $programTes = ProgramTes::where('nama', 'EPT')->first();

        $this->delete("/admin/data-induk/program-tes/{$programTes->id}")->assertRedirect();

        $this->assertDatabaseHas('program_tes', ['id' => $programTes->id]);
        $this->assertTrue(session()->has('error'));
    }

    public function test_program_tes_dengan_aturan_penilaian_tidak_dapat_dihapus(): void
    {
        $this->masukSebagai('admin');

        $programTes = ProgramTes::create([
            'kode' => '011',
            'nama' => 'Jenis Dengan Scoring',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);

        AturanPenilaian::create([
            'program_tes_id' => $programTes->id,
            'kode_section' => '011001',
            'tabel_konversi' => ['0' => 0],
            'skor_mentah_maksimum' => 1,
            'skor_skala_maksimum' => 1,
        ]);

        $this->delete("/admin/data-induk/program-tes/{$programTes->id}")
            ->assertRedirect();

        $this->assertDatabaseHas('program_tes', ['id' => $programTes->id]);
        $this->assertDatabaseHas('aturan_penilaian', ['program_tes_id' => $programTes->id]);
    }

    public function test_program_tes_nonaktif_tidak_dapat_dipakai_bank_soal_baru(): void
    {
        $this->masukSebagai('admin');

        $programTes = ProgramTes::create([
            'kode' => '012',
            'nama' => 'Jenis Nonaktif',
            'batas_pelanggaran' => 3,
            'aktif' => false,
        ]);

        $this->post('/pustaka-soal/bank-soal', [
            'program_tes_id' => $programTes->id,
            'nama' => 'Bank Nonaktif',
        ])->assertSessionHasErrors('program_tes_id');

        $this->assertDatabaseMissing('bank_soal', ['nama' => 'Bank Nonaktif']);
    }

    public function test_ujian_menolak_bank_soal_dari_program_tes_lain(): void
    {
        $this->masukSebagai('admin');

        $programTes = ProgramTes::create([
            'kode' => '013',
            'nama' => 'Jenis Lain',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);
        $bankSoal = BankSoal::where('nama', 'Bank Soal Latihan')->first();

        $this->post('/admin/ujian', [
            'program_tes_id' => $programTes->id,
            'bank_soal_id' => $bankSoal->id,
            'judul' => 'Ujian Dengan Bank Tidak Sesuai',
            'mode' => 'tryout',
            'durasi_menit' => 60,
        ])->assertSessionHasErrors('bank_soal_id');

        $this->assertDatabaseMissing('ujian', ['judul' => 'Ujian Dengan Bank Tidak Sesuai']);
    }

    public function test_nama_program_tes_unik_pada_database(): void
    {
        ProgramTes::create([
            'kode' => '014',
            'nama' => 'Nama Unik Database',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);

        $this->expectException(QueryException::class);

        ProgramTes::create([
            'kode' => '015',
            'nama' => 'Nama Unik Database',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);
    }
}
