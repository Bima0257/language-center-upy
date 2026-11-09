<?php

namespace Tests\Feature\Ujian;

use App\Enums\ModeUjian;
use App\Enums\StatusPendaftaranUjian;
use App\Enums\StatusSesi;
use App\Models\JenisTes;
use App\Models\PendaftaranUjian;
use App\Models\Pengguna;
use App\Models\PeriodePendaftaranUjian;
use App\Models\PeriodeUjian;
use App\Models\SesiUjian;
use App\Models\SlotUjian;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PendaftaranUjianTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Storage::fake('public');
    }

    private function masukSebagai(string $peran): Pengguna
    {
        $pengguna = Pengguna::where('email', $peran.'@toefl.test')->firstOrFail();

        $this->post('/login', [
            'email' => $pengguna->email,
            'password' => 'password',
        ]);

        return $pengguna;
    }

    private function buatSlot(string $mode, Carbon $tanggal, int $kapasitas = 30): SlotUjian
    {
        $jenisTes = JenisTes::firstOrFail();
        $ujian = Ujian::create([
            'jenis_tes_id' => $jenisTes->id,
            'judul' => 'Ujian Pendaftaran '.strtoupper($mode),
            'mode' => $mode,
            'durasi_menit' => 60,
            'aktif' => true,
        ]);
        $periode = PeriodeUjian::create([
            'ujian_id' => $ujian->id,
            'judul' => 'Periode Pendaftaran '.strtoupper($mode),
            'tanggal_mulai' => $tanggal->copy()->subDay()->toDateString(),
            'tanggal_selesai' => $tanggal->copy()->addDay()->toDateString(),
            'aktif' => true,
        ]);
        PeriodePendaftaranUjian::create([
            'periode_ujian_id' => $periode->id,
            'nama' => 'Pendaftaran '.strtoupper($mode),
            'dibuka_pada' => now()->subDay(),
            'ditutup_pada' => now()->addDays(2),
            'aktif' => true,
        ]);

        return SlotUjian::create([
            'periode_ujian_id' => $periode->id,
            'tanggal' => $tanggal->toDateString(),
            'waktu_mulai' => $tanggal->format('H:i:s'),
            'waktu_selesai' => $tanggal->copy()->addHours(2)->format('H:i:s'),
            'toleransi_keterlambatan_menit' => 15,
            'kapasitas_peserta' => $kapasitas,
            'aktif' => true,
        ]);
    }

    public function test_admin_dapat_membuka_manajemen_pendaftaran(): void
    {
        $this->masukSebagai('admin');

        $this->get('/admin/pendaftaran-ujian')->assertOk();
    }

    public function test_mahasiswa_tidak_dapat_membuka_manajemen_pendaftaran_admin(): void
    {
        $this->masukSebagai('student');

        $this->get('/admin/pendaftaran-ujian')->assertForbidden();
    }

    public function test_admin_dapat_crud_periode_pendaftaran_dan_melihat_slotnya(): void
    {
        $this->masukSebagai('admin');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));
        $periodePendaftaran = $slot->periodeUjian->periodePendaftaran;

        $rootPage = $this->get('/admin/pendaftaran-ujian?jenis_tes_id='.$slot->periodeUjian->ujian->jenis_tes_id)
            ->assertOk()
            ->inertiaPage();
        $this->assertArrayHasKey('periodeUjian', $rootPage['props']);
        $this->assertTrue(collect($rootPage['props']['periodePendaftaranUjian']['data'])->contains(
            fn ($period) => $period['id'] === $periodePendaftaran->id,
        ));

        $detailPage = $this->get('/admin/pendaftaran-ujian/'.$periodePendaftaran->id)
            ->assertOk()
            ->inertiaPage();
        $this->assertSame($slot->id, $detailPage['props']['slotUjian'][0]['id']);

        $this->get('/admin/pendaftaran-ujian/'.$periodePendaftaran->id.'/peserta?slot_ujian_id='.$slot->id)
            ->assertOk();

        $this->put('/admin/pendaftaran-ujian/'.$periodePendaftaran->id, [
            'nama' => 'Periode Pendaftaran Diperbarui',
            'dibuka_pada' => now()->subDay()->format('Y-m-d H:i:s'),
            'ditutup_pada' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'aktif' => true,
            'catatan' => 'Catatan baru',
        ])->assertRedirect();
        $this->assertDatabaseHas('periode_pendaftaran_ujian', [
            'id' => $periodePendaftaran->id,
            'nama' => 'Periode Pendaftaran Diperbarui',
        ]);

        $this->delete('/admin/pendaftaran-ujian/'.$periodePendaftaran->id)->assertRedirect();
        $this->assertDatabaseMissing('periode_pendaftaran_ujian', ['id' => $periodePendaftaran->id]);
    }

    public function test_admin_melihat_ringkasan_periode_pendaftaran_per_jenis_tes(): void
    {
        $this->masukSebagai('admin');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));

        $page = $this->get('/admin/pendaftaran-ujian')
            ->assertOk()
            ->inertiaPage();
        $ringkasan = collect($page['props']['ringkasanJenisTes'])
            ->firstWhere('id', $slot->periodeUjian->ujian->jenis_tes_id);

        $this->assertNull($page['props']['jenisTesTerpilihId']);
        $this->assertNotNull($ringkasan);
        $this->assertGreaterThanOrEqual(1, $ringkasan['jumlahUjian']);
        $this->assertGreaterThanOrEqual(1, $ringkasan['jumlahPeriodePendaftaran']);
    }

    public function test_admin_dapat_membuka_daftar_periode_pendaftaran_per_jenis_tes(): void
    {
        $this->masukSebagai('admin');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));
        $jenisTesId = $slot->periodeUjian->ujian->jenis_tes_id;

        $page = $this->get('/admin/pendaftaran-ujian?jenis_tes_id='.$jenisTesId)
            ->assertOk()
            ->inertiaPage();

        $this->assertSame($jenisTesId, $page['props']['jenisTesTerpilihId']);
        $this->assertTrue(collect($page['props']['periodePendaftaranUjian']['data'])->every(
            fn (array $periode): bool => $periode['periode_ujian']['ujian']['jenis_tes_id'] === $jenisTesId,
        ));
        $this->assertTrue(collect($page['props']['periodeUjian'])->every(
            fn (array $periode): bool => $periode['ujian']['jenis_tes_id'] === $jenisTesId,
        ));
    }

    public function test_jenis_tes_dengan_ujian_aktif_tanpa_periode_pendaftaran_tetap_muncul(): void
    {
        $this->masukSebagai('admin');

        $jenisTes = JenisTes::create([
            'kode' => '010',
            'nama' => 'Jenis Tes Tanpa Pendaftaran',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);
        Ujian::create([
            'jenis_tes_id' => $jenisTes->id,
            'judul' => 'Ujian Aktif Tanpa Pendaftaran',
            'mode' => ModeUjian::OFFICIAL->value,
            'durasi_menit' => 60,
            'aktif' => true,
        ]);

        $page = $this->get('/admin/pendaftaran-ujian')
            ->assertOk()
            ->inertiaPage();
        $ringkasan = collect($page['props']['ringkasanJenisTes'])
            ->firstWhere('id', $jenisTes->id);

        $this->assertNotNull($ringkasan);
        $this->assertSame(1, (int) $ringkasan['jumlahUjian']);
        $this->assertSame(0, (int) $ringkasan['jumlahPeriodePendaftaran']);

        $detail = $this->get('/admin/pendaftaran-ujian?jenis_tes_id='.$jenisTes->id)
            ->assertOk()
            ->inertiaPage();

        $this->assertSame($jenisTes->id, $detail['props']['jenisTesTerpilihId']);
        $this->assertEmpty($detail['props']['periodePendaftaranUjian']['data']);
        $this->assertEmpty($detail['props']['periodeUjian']);
    }

    public function test_admin_tidak_dapat_menyimpan_periode_dengan_waktu_tutup_sebelum_dibuka(): void
    {
        $this->masukSebagai('admin');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));
        $periodePendaftaran = $slot->periodeUjian->periodePendaftaran;

        $this->put('/admin/pendaftaran-ujian/'.$periodePendaftaran->id, [
            'nama' => 'Periode Tidak Valid',
            'dibuka_pada' => now()->addHour()->format('Y-m-d H:i:s'),
            'ditutup_pada' => now()->subHour()->format('Y-m-d H:i:s'),
            'aktif' => true,
        ])->assertSessionHasErrors('ditutup_pada');

        $this->assertDatabaseHas('periode_pendaftaran_ujian', [
            'id' => $periodePendaftaran->id,
            'nama' => 'Pendaftaran OFFICIAL',
        ]);
    }

    public function test_admin_tidak_dapat_menghapus_periode_yang_sudah_memiliki_sesi(): void
    {
        $this->masukSebagai('admin');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));
        SesiUjian::create([
            'slot_ujian_id' => $slot->id,
            'pengguna_id' => Pengguna::where('email', 'student@toefl.test')->value('id'),
            'status' => StatusSesi::SUBMITTED,
            'dimulai_pada' => now(),
            'diserahkan_pada' => now()->addMinutes(30),
        ]);

        $periodePendaftaran = $slot->periodeUjian->periodePendaftaran;

        $this->delete('/admin/pendaftaran-ujian/'.$periodePendaftaran->id)
            ->assertRedirect()
            ->assertSessionHas('error', 'Periode pendaftaran tidak dapat dihapus karena sudah memiliki sesi ujian.');

        $this->assertDatabaseHas('periode_pendaftaran_ujian', ['id' => $periodePendaftaran->id]);
    }

    public function test_mahasiswa_tanpa_approval_identitas_dapat_membuka_jadwal(): void
    {
        $mahasiswa = Pengguna::create([
            'name' => 'Mahasiswa Tanpa Approval',
            'email' => 'unverified-student@toefl.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'aktif' => true,
        ]);
        $mahasiswa->assignRole('student');

        $this->post('/login', [
            'email' => $mahasiswa->email,
            'password' => 'password',
        ]);

        $this->get('/ujian/jadwal')->assertOk();
    }

    public function test_mahasiswa_dapat_menelusuri_jadwal_dengan_konteks_ujian(): void
    {
        $this->masukSebagai('student');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));
        $ujian = $slot->periodeUjian->ujian;

        $rootPage = $this->get('/ujian/jadwal')->assertOk()->inertiaPage();
        $this->assertTrue(collect($rootPage['props']['ringkasanJenisTes'])->contains('id', $ujian->jenis_tes_id));

        $this->get('/ujian/jadwal?jenis_tes_id='.$ujian->jenis_tes_id.'&ujian_id='.$ujian->id)
            ->assertOk();

        $periodPage = $this->get('/ujian/jadwal?jenis_tes_id='.$ujian->jenis_tes_id
            .'&ujian_id='.$ujian->id
            .'&periode_ujian_id='.$slot->periode_ujian_id)
            ->assertOk()
            ->inertiaPage();
        $this->assertSame($slot->periode_ujian_id, $periodPage['props']['periodeUjian'][0]['id']);
    }

    public function test_jadwal_menampilkan_jumlah_pendaftaran_yang_disetujui(): void
    {
        $mahasiswa = $this->masukSebagai('student');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));
        $pendaftaran = PendaftaranUjian::create([
            'slot_ujian_id' => $slot->id,
            'pengguna_id' => $mahasiswa->id,
            'status' => StatusPendaftaranUjian::APPROVED,
        ]);

        $page = $this->get('/ujian/jadwal?jenis_tes_id='.$slot->periodeUjian->ujian->jenis_tes_id
            .'&ujian_id='.$slot->periodeUjian->ujian_id
            .'&periode_ujian_id='.$slot->periode_ujian_id)
            ->assertOk()
            ->inertiaPage();

        $this->assertSame($pendaftaran->slot_ujian_id, $page['props']['periodeUjian'][0]['slot_ujian'][0]['id']);
        $this->assertSame(1, $page['props']['periodeUjian'][0]['slot_ujian'][0]['pendaftaran_disetujui_count']);
    }

    public function test_mahasiswa_mengajukan_pendaftaran_official_dengan_bukti_pembayaran(): void
    {
        $mahasiswa = $this->masukSebagai('student');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));

        $this->post('/ujian/slot/'.$slot->id.'/pendaftaran', [
            'nim' => '2200099999',
            'fakultas_id' => $mahasiswa->profilMahasiswa->fakultas_id,
            'program_studi_id' => $mahasiswa->profilMahasiswa->program_studi_id,
            'tahun_angkatan' => 2022,
            'bukti_pembayaran' => UploadedFile::fake()->image('bukti.png'),
        ])->assertRedirect();

        $this->assertDatabaseHas('pendaftaran_ujian', [
            'slot_ujian_id' => $slot->id,
            'pengguna_id' => $mahasiswa->id,
            'status' => StatusPendaftaranUjian::PENDING->value,
        ]);
        $this->assertDatabaseHas('profil_mahasiswa', [
            'pengguna_id' => $mahasiswa->id,
            'nim' => '2200099999',
        ]);
    }

    public function test_pendaftaran_official_menolak_bukti_pembayaran_yang_tidak_diunggah(): void
    {
        $mahasiswa = $this->masukSebagai('student');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));

        $this->post('/ujian/slot/'.$slot->id.'/pendaftaran', [
            'nim' => $mahasiswa->profilMahasiswa->nim,
            'fakultas_id' => $mahasiswa->profilMahasiswa->fakultas_id,
            'program_studi_id' => $mahasiswa->profilMahasiswa->program_studi_id,
            'tahun_angkatan' => $mahasiswa->profilMahasiswa->tahun_angkatan,
        ])->assertSessionHasErrors('bukti_pembayaran');

        $this->assertDatabaseCount('pendaftaran_ujian', 0);
    }

    public function test_pendaftaran_official_ditolak_setelah_periode_pendaftaran_ditutup(): void
    {
        $mahasiswa = $this->masukSebagai('student');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));
        $slot->periodeUjian->periodePendaftaran->update([
            'ditutup_pada' => now()->subMinute(),
        ]);

        $this->post('/ujian/slot/'.$slot->id.'/pendaftaran', [
            'nim' => $mahasiswa->profilMahasiswa->nim,
            'fakultas_id' => $mahasiswa->profilMahasiswa->fakultas_id,
            'program_studi_id' => $mahasiswa->profilMahasiswa->program_studi_id,
            'tahun_angkatan' => $mahasiswa->profilMahasiswa->tahun_angkatan,
            'bukti_pembayaran' => UploadedFile::fake()->image('bukti.png'),
        ])->assertRedirect()->assertSessionHas('error', 'Pendaftaran ujian sudah ditutup.');

        $this->assertDatabaseCount('pendaftaran_ujian', 0);
    }

    public function test_admin_dapat_menyetujui_pendaftaran_dan_mahasiswa_dapat_membatalkannya(): void
    {
        $mahasiswa = $this->masukSebagai('student');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));
        $this->post('/ujian/slot/'.$slot->id.'/pendaftaran', [
            'nim' => $mahasiswa->profilMahasiswa->nim,
            'fakultas_id' => $mahasiswa->profilMahasiswa->fakultas_id,
            'program_studi_id' => $mahasiswa->profilMahasiswa->program_studi_id,
            'tahun_angkatan' => $mahasiswa->profilMahasiswa->tahun_angkatan,
            'bukti_pembayaran' => UploadedFile::fake()->image('bukti.png'),
        ])->assertRedirect();

        $pendaftaran = PendaftaranUjian::firstOrFail();
        $this->post('/logout');
        $this->masukSebagai('admin');

        $this->patch('/admin/pendaftaran-ujian/'.$pendaftaran->id.'/setujui', [
            'catatan_admin' => 'Pembayaran sesuai.',
        ])->assertRedirect();
        $this->assertDatabaseHas('pendaftaran_ujian', [
            'id' => $pendaftaran->id,
            'status' => StatusPendaftaranUjian::APPROVED->value,
        ]);

        $this->post('/logout');
        $this->masukSebagai('student');
        $this->delete('/ujian/pendaftaran/'.$pendaftaran->id)->assertRedirect();

        $this->assertDatabaseHas('pendaftaran_ujian', [
            'id' => $pendaftaran->id,
            'status' => StatusPendaftaranUjian::CANCELLED->value,
        ]);
    }

    public function test_official_yang_menunggu_tidak_dapat_masuk_ke_pemeriksaan_awal(): void
    {
        $mahasiswa = $this->masukSebagai('student');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));

        $this->post('/ujian/slot/'.$slot->id.'/pendaftaran', [
            'nim' => $mahasiswa->profilMahasiswa->nim,
            'fakultas_id' => $mahasiswa->profilMahasiswa->fakultas_id,
            'program_studi_id' => $mahasiswa->profilMahasiswa->program_studi_id,
            'tahun_angkatan' => $mahasiswa->profilMahasiswa->tahun_angkatan,
            'bukti_pembayaran' => UploadedFile::fake()->image('bukti.png'),
        ])->assertRedirect();

        $this->get('/ujian/slot/'.$slot->id.'/pemeriksaan-awal')
            ->assertRedirect(route('ujian.jadwal'))
            ->assertSessionHas('error', 'Pendaftaran ujian Anda belum disetujui.');
    }

    public function test_tryout_membuat_pendaftaran_approved_saat_sesi_dimulai(): void
    {
        $mahasiswa = $this->masukSebagai('student');
        $slot = $this->buatSlot(ModeUjian::TRYOUT->value, now()->subMinutes(5));

        $this->post('/ujian/slot/'.$slot->id.'/mulai', [
            'jenis_perangkat' => 'desktop',
            'agen_pengguna_perangkat' => 'PendaftaranUjianTest',
            'swafoto' => UploadedFile::fake()->image('selfie.jpg'),
        ])->assertRedirect();

        $this->assertDatabaseHas('pendaftaran_ujian', [
            'slot_ujian_id' => $slot->id,
            'pengguna_id' => $mahasiswa->id,
            'status' => StatusPendaftaranUjian::APPROVED->value,
        ]);
        $this->assertDatabaseHas('sesi_ujian', [
            'slot_ujian_id' => $slot->id,
            'pengguna_id' => $mahasiswa->id,
            'status' => StatusSesi::IN_PROGRESS->value,
        ]);
    }
}
