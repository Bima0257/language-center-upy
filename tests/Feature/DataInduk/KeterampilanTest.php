<?php

namespace Tests\Feature\DataInduk;

use App\Models\JenisTes;
use App\Models\Keterampilan;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeterampilanTest extends TestCase
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
        $this->get('/admin/data-induk/keterampilan')->assertRedirect('/login');
    }

    public function test_peran_tanpa_izin_ditolak(): void
    {
        $this->masukSebagai('student');

        $this->get('/admin/data-induk/keterampilan')->assertForbidden();
    }

    public function test_admin_dapat_melihat_ringkasan_dan_detail_keterampilan_per_jenis_tes(): void
    {
        $this->masukSebagai('admin');
        $jenisTes = JenisTes::where('nama', 'TOEFL iBT')->firstOrFail();

        $this->get('/admin/data-induk/keterampilan')->assertOk();
        $this->get("/admin/data-induk/keterampilan/jenis-tes/{$jenisTes->id}")->assertOk();
    }

    public function test_admin_dapat_membuat_keterampilan_dengan_kode_otomatis(): void
    {
        $this->masukSebagai('admin');
        $jenisTes = JenisTes::where('nama', 'TOEFL iBT')->firstOrFail();

        $this->post('/admin/data-induk/keterampilan', [
            'jenis_tes_id' => $jenisTes->id,
            'nama' => 'Speaking',
            'aktif' => true,
        ])->assertRedirect();

        $keterampilan = Keterampilan::where('jenis_tes_id', $jenisTes->id)
            ->where('nama', 'Speaking')
            ->firstOrFail();

        $this->assertSame('001003', $keterampilan->kode);
    }

    public function test_nama_keterampilan_duplikat_dalam_jenis_tes_ditolak(): void
    {
        $this->masukSebagai('admin');
        $jenisTes = JenisTes::where('nama', 'TOEFL iBT')->firstOrFail();

        $this->post('/admin/data-induk/keterampilan', [
            'jenis_tes_id' => $jenisTes->id,
            'nama' => 'Reading',
            'aktif' => true,
        ])->assertSessionHasErrors('nama');
    }

    public function test_nama_keterampilan_sama_diperbolehkan_pada_jenis_tes_lain(): void
    {
        $this->masukSebagai('admin');
        $jenisTes = JenisTes::create([
            'kode' => '002',
            'nama' => 'IELTS',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);

        $this->post('/admin/data-induk/keterampilan', [
            'jenis_tes_id' => $jenisTes->id,
            'nama' => 'Reading',
            'aktif' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('keterampilan', [
            'jenis_tes_id' => $jenisTes->id,
            'kode' => '002001',
            'nama' => 'Reading',
        ]);
    }

    public function test_kode_keterampilan_tidak_dapat_diubah(): void
    {
        $this->masukSebagai('admin');
        $keterampilan = Keterampilan::where('nama', 'Reading')->firstOrFail();
        $kodeAwal = $keterampilan->kode;

        $this->put("/admin/data-induk/keterampilan/{$keterampilan->id}", [
            'nama' => 'Reading Updated',
            'aktif' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('keterampilan', [
            'id' => $keterampilan->id,
            'kode' => $kodeAwal,
            'nama' => 'Reading Updated',
        ]);
    }

    public function test_kode_tidak_digunakan_kembali_setelah_keterampilan_dihapus(): void
    {
        $this->masukSebagai('admin');
        $jenisTes = JenisTes::where('nama', 'TOEFL iBT')->firstOrFail();

        $this->post('/admin/data-induk/keterampilan', [
            'jenis_tes_id' => $jenisTes->id,
            'nama' => 'Speaking',
            'aktif' => true,
        ])->assertRedirect();

        $keterampilan = Keterampilan::where('nama', 'Speaking')->firstOrFail();
        $kodeDihapus = $keterampilan->kode;

        $this->delete("/admin/data-induk/keterampilan/{$keterampilan->id}")->assertRedirect();

        $this->post('/admin/data-induk/keterampilan', [
            'jenis_tes_id' => $jenisTes->id,
            'nama' => 'Writing',
            'aktif' => true,
        ])->assertRedirect();

        $this->assertNotSame($kodeDihapus, Keterampilan::where('nama', 'Writing')->firstOrFail()->kode);
    }
}
