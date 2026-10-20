<?php

namespace Tests\Feature\Penjadwalan;

use App\Models\JenisTes;
use App\Models\Pengguna;
use App\Models\PeriodeUjian;
use App\Models\SesiUjian;
use App\Models\SlotUjian;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodeUjianTest extends TestCase
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

    private function ujian(): Ujian
    {
        return Ujian::first();
    }

    private function atributPeriode(array $overrides = []): array
    {
        return array_merge([
            'judul' => 'Gelombang Baru',
            'tanggal_mulai' => now()->addDays(7)->format('Y-m-d'),
            'tanggal_selesai' => now()->addDays(9)->format('Y-m-d'),
        ], $overrides);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/admin/periode-ujian')->assertRedirect('/login');
    }

    public function test_peran_tanpa_izin_ditolak(): void
    {
        $this->masukSebagai('student');

        $this->get('/admin/periode-ujian')->assertForbidden();
    }

    public function test_admin_dapat_mengakses_semua_periode_ujian(): void
    {
        $this->masukSebagai('admin');

        $this->get('/admin/periode-ujian')->assertOk();
    }

    public function test_admin_dapat_mengakses_halaman_tambah(): void
    {
        $this->masukSebagai('admin');

        $this->get("/admin/ujian/{$this->ujian()->id}/periode-ujian/tambah")->assertOk();
    }

    public function test_admin_dapat_membuat_periode(): void
    {
        $this->masukSebagai('admin');

        $this->post("/admin/ujian/{$this->ujian()->id}/periode-ujian", $this->atributPeriode())
            ->assertRedirect();

        $this->assertDatabaseHas('periode_ujian', [
            'ujian_id' => $this->ujian()->id,
            'judul' => 'Gelombang Baru',
        ]);
    }

    public function test_periode_tumpang_tindih_ditolak(): void
    {
        $this->masukSebagai('admin');

        $existing = PeriodeUjian::first();

        $this->post("/admin/ujian/{$this->ujian()->id}/periode-ujian", $this->atributPeriode([
            'tanggal_mulai' => $existing->tanggal_mulai->format('Y-m-d'),
            'tanggal_selesai' => $existing->tanggal_selesai->format('Y-m-d'),
        ]))->assertRedirect();

        $this->assertDatabaseMissing('periode_ujian', [
            'ujian_id' => $this->ujian()->id,
            'judul' => 'Gelombang Baru',
        ]);
    }

    public function test_admin_dapat_mengakses_halaman_periode(): void
    {
        $this->masukSebagai('admin');

        $periodeUjian = PeriodeUjian::first();

        $this->get("/admin/periode-ujian/{$periodeUjian->id}")->assertOk();
    }

    public function test_admin_dapat_membuat_slot(): void
    {
        $this->masukSebagai('admin');

        $periodeUjian = PeriodeUjian::first();

        $this->post("/admin/periode-ujian/{$periodeUjian->id}/slot", [
            'tanggal' => $periodeUjian->tanggal_mulai->format('Y-m-d'),
            'waktu_mulai' => '09:00',
            'waktu_selesai' => '11:00',
            'toleransi_keterlambatan_menit' => 15,
            'kapasitas_peserta' => 25,
        ])->assertRedirect();

        $this->assertDatabaseHas('slot_ujian', [
            'periode_ujian_id' => $periodeUjian->id,
            'waktu_mulai' => '09:00',
        ]);
    }

    public function test_tanggal_slot_di_luar_periode_ditolak(): void
    {
        $this->masukSebagai('admin');

        $periodeUjian = PeriodeUjian::first();

        $this->post("/admin/periode-ujian/{$periodeUjian->id}/slot", [
            'tanggal' => now()->addMonths(3)->format('Y-m-d'),
            'waktu_mulai' => '09:00',
            'waktu_selesai' => '11:00',
        ])->assertSessionHasErrors('tanggal');

        $this->assertDatabaseCount('slot_ujian', 3);
    }

    public function test_slot_tumpang_tindih_ditolak(): void
    {
        $this->masukSebagai('admin');

        $periodeUjian = PeriodeUjian::first();
        $existing = SlotUjian::where('periode_ujian_id', $periodeUjian->id)->first();

        $this->post("/admin/periode-ujian/{$periodeUjian->id}/slot", [
            'tanggal' => $existing->tanggal->format('Y-m-d'),
            'waktu_mulai' => substr((string) $existing->waktu_mulai, 0, 5),
            'waktu_selesai' => substr((string) $existing->waktu_selesai, 0, 5),
            'kapasitas_peserta' => 30,
        ])->assertRedirect();

        $this->assertDatabaseCount('slot_ujian', 3);
    }

    public function test_admin_tidak_dapat_menghapus_periode_dengan_slot(): void
    {
        $this->masukSebagai('admin');

        $periodeUjian = PeriodeUjian::first();

        $this->delete("/admin/periode-ujian/{$periodeUjian->id}")->assertRedirect();

        $this->assertDatabaseHas('periode_ujian', ['id' => $periodeUjian->id]);
    }

    public function test_admin_dapat_menghapus_periode_tanpa_slot(): void
    {
        $this->masukSebagai('admin');

        $periodeUjian = PeriodeUjian::create([
            'ujian_id' => $this->ujian()->id,
            'judul' => 'Periode Kosong',
            'tanggal_mulai' => now()->addDays(20),
            'tanggal_selesai' => now()->addDays(21),
            'aktif' => true,
        ]);

        $this->delete("/admin/periode-ujian/{$periodeUjian->id}")->assertRedirect();

        $this->assertDatabaseMissing('periode_ujian', ['id' => $periodeUjian->id]);
    }

    public function test_admin_tidak_dapat_menghapus_slot_dengan_peserta(): void
    {
        $this->masukSebagai('admin');

        $slotUjian = SlotUjian::whereHas('sesiUjian')->first();
        $this->assertNotNull($slotUjian);

        $this->delete("/admin/periode-ujian/slot/{$slotUjian->id}")->assertRedirect();

        $this->assertDatabaseHas('slot_ujian', ['id' => $slotUjian->id]);
    }

    public function test_admin_dapat_menghapus_slot_kosong(): void
    {
        $this->masukSebagai('admin');

        $periodeUjian = PeriodeUjian::first();

        $slotUjian = SlotUjian::create([
            'periode_ujian_id' => $periodeUjian->id,
            'tanggal' => $periodeUjian->tanggal_mulai,
            'waktu_mulai' => '12:00',
            'waktu_selesai' => '14:00',
            'kapasitas_peserta' => 30,
            'aktif' => true,
        ]);

        $this->delete("/admin/periode-ujian/slot/{$slotUjian->id}")->assertRedirect();

        $this->assertDatabaseMissing('slot_ujian', ['id' => $slotUjian->id]);
    }

    public function test_mahasiswa_tidak_dapat_mengikuti_periode_sama_dua_kali(): void
    {
        $mahasiswa = $this->masukSebagai('student');

        $jenisTes = JenisTes::where('nama', 'TOEFL iBT')->first();
        $ujian = $this->ujian();

        $periodeUjian = PeriodeUjian::create([
            'ujian_id' => $ujian->id,
            'judul' => 'Periode Percobaan',
            'tanggal_mulai' => now()->addDays(10),
            'tanggal_selesai' => now()->addDays(11),
            'aktif' => true,
        ]);

        $slotUjian = SlotUjian::create([
            'periode_ujian_id' => $periodeUjian->id,
            'tanggal' => now()->addDays(10)->toDateString(),
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '10:00',
            'kapasitas_peserta' => 30,
            'aktif' => true,
        ]);

        SesiUjian::create([
            'periode_ujian_id' => $periodeUjian->id,
            'slot_ujian_id' => $slotUjian->id,
            'pengguna_id' => $mahasiswa->id,
            'status' => 'submitted',
            'dimulai_pada' => now(),
            'diserahkan_pada' => now()->addMinutes(30),
        ]);

        $this->post("/ujian/slot/{$slotUjian->id}/mulai")->assertRedirect();

        $this->assertDatabaseCount('sesi_ujian', 4);
    }
}
