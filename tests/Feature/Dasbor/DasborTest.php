<?php

namespace Tests\Feature\Dasbor;

use App\Models\Pengguna;
use App\Models\PeriodeUjian;
use App\Models\ProgramTes;
use App\Models\SlotUjian;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DasborTest extends TestCase
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
        $this->get('/dasbor')->assertRedirect('/login');
    }

    public function test_admin_dapat_mengakses_dasbor(): void
    {
        $this->masukSebagai('admin');

        $this->get('/dasbor')->assertOk();
    }

    public function test_admin_dapat_mengakses_dasbor_konten(): void
    {
        $this->masukSebagai('admin');

        $this->get('/dasbor')->assertOk();
    }

    public function test_mahasiswa_dapat_mengakses_dasbor(): void
    {
        $this->masukSebagai('peserta');

        $this->get('/dasbor')->assertOk();
    }

    public function test_dasbor_mahasiswa_dengan_waktu_slot_memuat_detik(): void
    {
        $programTes = ProgramTes::firstOrCreate(['nama' => 'EPT'], [
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);

        $ujian = Ujian::create([
            'program_tes_id' => $programTes->id,
            'judul' => 'Test Carbon',
            'deskripsi' => 'Regression test',
            'mode' => 'tryout',
            'durasi_menit' => 60,
            'aktif' => true,
        ]);

        $periodeUjian = PeriodeUjian::create([
            'ujian_id' => $ujian->id,
            'judul' => 'Slot Carbon',
            'tanggal_mulai' => now()->subDay(),
            'tanggal_selesai' => now()->addDay(),
            'aktif' => true,
        ]);

        SlotUjian::create([
            'periode_ujian_id' => $periodeUjian->id,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => now()->subMinutes(5)->format('H:i:s'),
            'waktu_selesai' => now()->addHours(2)->format('H:i:s'),
            'toleransi_keterlambatan_menit' => 15,
            'kapasitas_peserta' => 30,
            'aktif' => true,
        ]);

        $this->masukSebagai('peserta');

        $this->get('/dasbor')->assertOk();
    }
}
