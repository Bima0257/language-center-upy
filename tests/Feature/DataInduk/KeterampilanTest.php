<?php

namespace Tests\Feature\DataInduk;

use App\Models\JenisTes;
use App\Models\KebijakanMateriSoal;
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

    public function test_admin_dapat_menyimpan_urutan_keterampilan(): void
    {
        $this->masukSebagai('admin');
        $jenisTes = JenisTes::where('nama', 'TOEFL iBT')->firstOrFail();
        $keterampilan = Keterampilan::where('jenis_tes_id', $jenisTes->id)
            ->orderBy('urutan')
            ->get();

        $this->assertGreaterThan(1, $keterampilan->count());

        $reversed = $keterampilan->pluck('id')->reverse()->values()->all();

        $this->post('/admin/data-induk/keterampilan/urutkan', [
            'keterampilan' => $reversed,
            'jenis_tes_id' => $jenisTes->id,
        ])->assertRedirect();

        $updated = Keterampilan::where('jenis_tes_id', $jenisTes->id)
            ->orderBy('urutan')
            ->pluck('id')
            ->all();

        $this->assertSame($reversed, $updated);
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

    public function test_admin_dapat_menyimpan_policy_materi_per_keterampilan(): void
    {
        $this->masukSebagai('admin');
        $jenisTes = JenisTes::where('nama', 'TOEFL iBT')->firstOrFail();

        $this->post('/admin/data-induk/keterampilan', [
            'jenis_tes_id' => $jenisTes->id,
            'nama' => 'Speaking Audio',
            'aktif' => true,
            'ketentuan_materi' => 'required',
            'aturan_media' => [
                'text' => 'forbidden',
                'audio' => 'required',
                'image' => 'optional',
            ],
        ])->assertRedirect();

        $keterampilan = Keterampilan::where('nama', 'Speaking Audio')->firstOrFail();
        $kebijakan = KebijakanMateriSoal::where('keterampilan_id', $keterampilan->id)->firstOrFail();

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
        $jenisTes = JenisTes::where('nama', 'TOEFL iBT')->firstOrFail();

        $this->post('/admin/data-induk/keterampilan', [
            'jenis_tes_id' => $jenisTes->id,
            'nama' => 'Policy Kontradiktif',
            'aktif' => true,
            'ketentuan_materi' => 'optional',
            'aturan_media' => [
                'text' => 'optional',
                'audio' => 'required',
                'image' => 'forbidden',
            ],
        ])->assertSessionHasErrors('ketentuan_materi');

        $this->assertDatabaseMissing('keterampilan', ['nama' => 'Policy Kontradiktif']);
    }

    public function test_policy_materi_dapat_dikosongkan_jika_keterampilan_belum_dipakai_soal(): void
    {
        $this->masukSebagai('admin');
        $jenisTes = JenisTes::where('nama', 'TOEFL iBT')->firstOrFail();

        $this->post('/admin/data-induk/keterampilan', [
            'jenis_tes_id' => $jenisTes->id,
            'nama' => 'Policy Yang Dikosongkan',
            'aktif' => true,
            'ketentuan_materi' => 'required',
            'aturan_media' => [
                'text' => 'required',
                'audio' => 'forbidden',
                'image' => 'optional',
            ],
        ])->assertRedirect();

        $keterampilan = Keterampilan::where('nama', 'Policy Yang Dikosongkan')->firstOrFail();

        $this->put("/admin/data-induk/keterampilan/{$keterampilan->id}", [
            'nama' => $keterampilan->nama,
            'deskripsi' => null,
            'aktif' => true,
            'ketentuan_materi' => null,
            'aturan_media' => [
                'text' => null,
                'audio' => null,
                'image' => null,
            ],
        ])->assertRedirect();

        $this->assertDatabaseMissing('kebijakan_materi_soal', [
            'keterampilan_id' => $keterampilan->id,
        ]);
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
        ])->assertRedirect()->assertSessionDoesntHaveErrors();

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
