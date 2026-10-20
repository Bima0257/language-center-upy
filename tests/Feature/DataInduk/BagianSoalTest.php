<?php

namespace Tests\Feature\DataInduk;

use App\Models\BagianSoal;
use App\Models\BankSoal;
use App\Models\JenisTes;
use App\Models\Keterampilan;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BagianSoalTest extends TestCase
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
        $this->get('/admin/data-induk/bagian-soal')->assertRedirect('/login');
    }

    public function test_peran_tanpa_izin_ditolak(): void
    {
        $this->masukSebagai('student');

        $this->get('/admin/data-induk/bagian-soal')->assertForbidden();
    }

    public function test_admin_dapat_mengakses_halaman(): void
    {
        $this->masukSebagai('admin');

        $this->get('/admin/data-induk/bagian-soal')->assertOk();
    }

    public function test_admin_dapat_menavigasi_hierarki_jenis_tes_bank_dan_bagian_soal(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal 2022')->firstOrFail();
        $jenisTes = $bankSoal->jenisTes;
        $root = $this->get('/admin/data-induk/bagian-soal')
            ->assertOk()
            ->inertiaPage();

        $bank = $this->get('/admin/data-induk/bagian-soal?jenis_tes_id='.$jenisTes->id)
            ->assertOk()
            ->inertiaPage();

        $bankIds = collect($bank['props']['bankSoal'])->pluck('id')->map(fn ($id) => (string) $id);
        $bagianSoal = collect($bank['props']['bagianSoal']);

        $this->assertNull($root['props']['jenisTesTerpilihId']);
        $this->assertTrue(collect($root['props']['jenisTes'])->contains(fn (array $jenis) => $jenis['id'] === $jenisTes->id));
        $this->assertSame($jenisTes->id, $bank['props']['jenisTesTerpilihId']);
        $this->assertTrue($bankIds->contains((string) $bankSoal->id));
        $this->assertTrue(
            $bagianSoal->every(fn (array $bagian) => $bankIds->contains((string) $bagian['bank_soal_id'])),
            'Daftar bagian pada detail jenis tes harus hanya berasal dari bank soal jenis tes tersebut.',
        );
    }

    public function test_admin_dapat_melihat_ringkasan_dan_detail_bagian_soal_per_bank(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal 2022')->first();
        $ringkasan = $this->get('/admin/data-induk/bagian-soal')
            ->assertOk()
            ->inertiaPage();

        $detail = $this->get('/admin/data-induk/bagian-soal?bank_id='.$bankSoal->id)
            ->assertOk()
            ->inertiaPage();

        $bagianSoal = collect($detail['props']['bagianSoal']);

        $this->assertNull($ringkasan['props']['bankSoalTerpilihId']);
        $this->assertSame($bankSoal->id, $detail['props']['bankSoalTerpilihId']);
        $this->assertSame($bankSoal->jenis_tes_id, $detail['props']['jenisTesTerpilihId']);
        $this->assertTrue(
            $bagianSoal->every(fn (array $bagian) => (string) $bagian['bank_soal_id'] === (string) $bankSoal->id),
            'Detail harus hanya memuat bagian soal dari bank terpilih.',
        );
        $this->assertTrue(
            collect($ringkasan['props']['bankSoal'])->contains(fn (array $bank) => $bank['id'] === $bankSoal->id),
            'Ringkasan harus memuat bank soal yang digunakan untuk detail.',
        );
    }

    public function test_admin_dapat_menyaring_bagian_berdasarkan_bank(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal 2022')->first();

        $this->get('/admin/data-induk/bagian-soal?bank_id='.$bankSoal->id)->assertOk();
    }

    public function test_pembuatan_bagian_mewajibkan_bank(): void
    {
        $this->masukSebagai('admin');

        $keterampilan = Keterampilan::where('nama', 'Reading')->first();

        $this->post('/admin/data-induk/bagian-soal', [
            'keterampilan_id' => $keterampilan->id,
            'nama' => 'Part X',
            'urutan' => 1,
        ])->assertSessionHasErrors('bank_soal_id');
    }

    public function test_admin_dapat_membuat_bagian_untuk_bank(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal 2022')->first();
        $keterampilan = Keterampilan::where('nama', 'Reading')->first();

        $this->post('/admin/data-induk/bagian-soal', [
            'bank_soal_id' => $bankSoal->id,
            'keterampilan_id' => $keterampilan->id,
            'nama' => 'Part Baru',
            'urutan' => 9,
        ])->assertRedirect();

        $this->assertDatabaseHas('bagian_soal', [
            'bank_soal_id' => $bankSoal->id,
            'keterampilan_id' => $keterampilan->id,
            'nama' => 'Part Baru',
        ]);
    }

    public function test_nama_bagian_duplikat_pada_bank_dan_keterampilan_sama_ditolak(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal 2022')->first();
        $keterampilan = Keterampilan::where('nama', 'Reading')->first();
        $existing = BagianSoal::where('bank_soal_id', $bankSoal->id)->where('keterampilan_id', $keterampilan->id)->first();

        $this->post('/admin/data-induk/bagian-soal', [
            'bank_soal_id' => $bankSoal->id,
            'keterampilan_id' => $keterampilan->id,
            'nama' => $existing->nama,
            'urutan' => 1,
        ])->assertSessionHasErrors('nama');
    }

    public function test_nama_bagian_sama_diperbolehkan_pada_bank_lain(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal 2022')->first();
        $keterampilan = Keterampilan::where('nama', 'Reading')->first();
        $existing = BagianSoal::where('bank_soal_id', $bankSoal->id)->where('keterampilan_id', $keterampilan->id)->first();

        $bankSoalLain = BankSoal::create([
            'nama' => 'Bank Baru',
            'jenis_tes_id' => JenisTes::first()->id,
            'aktif' => true,
        ]);

        $this->post('/admin/data-induk/bagian-soal', [
            'bank_soal_id' => $bankSoalLain->id,
            'keterampilan_id' => $keterampilan->id,
            'nama' => $existing->nama,
            'urutan' => 1,
        ])->assertRedirect();
    }
}
