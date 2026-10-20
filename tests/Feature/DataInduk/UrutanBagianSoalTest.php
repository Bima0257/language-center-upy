<?php

namespace Tests\Feature\DataInduk;

use App\Models\BagianSoal;
use App\Models\BankSoal;
use App\Models\Keterampilan;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UrutanBagianSoalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function masukAdmin(): void
    {
        $pengguna = Pengguna::where('email', 'admin@toefl.test')->firstOrFail();

        $this->post('/login', [
            'email' => $pengguna->email,
            'password' => 'password',
        ])->assertRedirect();
    }

    public function test_simpan_bagian_menempatkan_urutan_otomatis_di_akhir(): void
    {
        $this->masukAdmin();

        $bankSoal = BankSoal::where('nama', 'Bank Soal 2022')->firstOrFail();
        $keterampilan = Keterampilan::where('nama', 'Reading')->firstOrFail();
        $urutanTerakhir = BagianSoal::where('bank_soal_id', $bankSoal->id)
            ->where('keterampilan_id', $keterampilan->id)
            ->max('urutan');

        $this->post('/admin/data-induk/bagian-soal', [
            'bank_soal_id' => $bankSoal->id,
            'keterampilan_id' => $keterampilan->id,
            'nama' => 'Part Auto Order',
            'petunjuk' => 'Instruksi test.',
            'aktif' => true,
        ])->assertRedirect();

        $bagianSoal = BagianSoal::where('bank_soal_id', $bankSoal->id)
            ->where('keterampilan_id', $keterampilan->id)
            ->where('nama', 'Part Auto Order')
            ->firstOrFail();

        $this->assertSame((int) $urutanTerakhir + 1, (int) $bagianSoal->urutan);
    }

    public function test_pengurutan_bagian_mengubah_urutan_sesuai_urutan_baru(): void
    {
        $this->masukAdmin();

        $bankSoal = BankSoal::where('nama', 'Bank Soal 2022')->firstOrFail();
        $keterampilan = Keterampilan::where('nama', 'Reading')->firstOrFail();
        $daftarBagianSoal = BagianSoal::where('bank_soal_id', $bankSoal->id)
            ->where('keterampilan_id', $keterampilan->id)
            ->orderBy('urutan')
            ->get();

        $this->assertGreaterThan(1, $daftarBagianSoal->count());

        $reversed = $daftarBagianSoal->pluck('id')->reverse()->values()->all();

        $this->post('/admin/data-induk/bagian-soal/urutkan', [
            'bagian_soal' => $reversed,
        ])->assertRedirect();

        $updated = BagianSoal::whereIn('id', $reversed)
            ->orderBy('urutan')
            ->pluck('id')
            ->all();

        $this->assertSame($reversed, $updated);
    }
}
