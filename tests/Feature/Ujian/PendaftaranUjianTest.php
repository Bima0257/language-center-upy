<?php

namespace Tests\Feature\Ujian;

use App\Enums\ModeUjian;
use App\Enums\StatusPendaftaranUjian;
use App\Enums\StatusSesi;
use App\Models\BankSoal;
use App\Models\PartUjian;
use App\Models\PendaftaranUjian;
use App\Models\Pengguna;
use App\Models\PeriodePendaftaranUjian;
use App\Models\PeriodeUjian;
use App\Models\ProgramTes;
use App\Models\Section;
use App\Models\SesiUjian;
use App\Models\SkemaPenilaian;
use App\Models\SlotUjian;
use App\Models\Soal;
use App\Models\Ujian;
use App\Modules\Penilaian\Services\LayananAturanPenilaian;
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
        $programTes = ProgramTes::firstOrFail();
        $ujian = Ujian::create([
            'program_tes_id' => $programTes->id,
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

    private function lengkapiSoalUjian(Ujian $ujian): void
    {
        $bankSoal = BankSoal::where('program_tes_id', $ujian->program_tes_id)->firstOrFail();

        foreach ([['Reading', 1], ['Listening', 2]] as [$namaSection, $urutan]) {
            $section = Section::where('program_tes_id', $ujian->program_tes_id)
                ->where('nama', $namaSection)
                ->firstOrFail();

            $part = PartUjian::create([
                'ujian_id' => $ujian->id,
                'bank_soal_id' => $bankSoal->id,
                'section_id' => $section->id,
                'judul' => $namaSection,
                'urutan' => $urutan,
                'jumlah_soal' => 1,
            ]);

            $part->soalPartUjian()->create([
                'soal_id' => Soal::create([
                    'bank_soal_id' => $bankSoal->id,
                    'section_id' => $section->id,
                    'teks_soal' => 'Soal '.$namaSection,
                    'pilihan_a' => 'A',
                    'pilihan_b' => 'B',
                    'pilihan_c' => 'C',
                    'pilihan_d' => 'D',
                    'kunci_jawaban' => 'A',
                    'urutan' => 1,
                ])->id,
                'urutan' => 1,
            ]);
        }
    }

    public function test_admin_dapat_membuka_manajemen_pendaftaran(): void
    {
        $this->masukSebagai('admin');

        $this->get('/admin/pendaftaran-ujian')->assertOk();
    }

    public function test_mahasiswa_tidak_dapat_membuka_manajemen_pendaftaran_admin(): void
    {
        $this->masukSebagai('peserta');

        $this->get('/admin/pendaftaran-ujian')->assertForbidden();
    }

    public function test_admin_dapat_crud_periode_pendaftaran_dan_melihat_slotnya(): void
    {
        $this->masukSebagai('admin');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));
        $periodePendaftaran = $slot->periodeUjian->periodePendaftaran;

        $rootPage = $this->get('/admin/pendaftaran-ujian?program_tes_id='.$slot->periodeUjian->ujian->program_tes_id)
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

    public function test_daftar_periode_pendaftaran_mendukung_q_status_sort_dan_per_page(): void
    {
        $this->masukSebagai('admin');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));
        $periode = $slot->periodeUjian->periodePendaftaran;
        $programTesId = $slot->periodeUjian->ujian->program_tes_id;

        $page = $this->get('/admin/pendaftaran-ujian?f_program_tes_id='.$programTesId
            .'&q='.urlencode($periode->nama)
            .'&f_status=open&per_page=25&sort=nama&dir=asc')
            ->assertOk()
            ->inertiaPage();

        $this->assertSame(25, $page['props']['periodePendaftaranUjian']['per_page']);
        $this->assertTrue(collect($page['props']['periodePendaftaranUjian']['data'])->contains(
            fn (array $item): bool => $item['id'] === $periode->id,
        ));
    }

    public function test_peserta_pendaftaran_mendukung_q_status_dan_per_page(): void
    {
        $this->masukSebagai('admin');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));
        $periodePendaftaran = $slot->periodeUjian->periodePendaftaran;
        $mahasiswa = Pengguna::where('email', 'peserta@toefl.test')->firstOrFail();

        $pendaftaran = PendaftaranUjian::create([
            'slot_ujian_id' => $slot->id,
            'pengguna_id' => $mahasiswa->id,
            'status' => StatusPendaftaranUjian::PENDING,
        ]);

        $page = $this->get('/admin/pendaftaran-ujian/'.$periodePendaftaran->id.'/peserta'
            .'?slot_ujian_id='.$slot->id
            .'&q='.urlencode($mahasiswa->name)
            .'&f_status=pending&per_page=25')
            ->assertOk()
            ->inertiaPage();

        $this->assertSame(25, $page['props']['pendaftaranUjian']['per_page']);
        $this->assertTrue(collect($page['props']['pendaftaranUjian']['data'])->contains(
            fn (array $item): bool => $item['id'] === $pendaftaran->id,
        ));
    }

    public function test_admin_melihat_ringkasan_periode_pendaftaran_per_program_tes(): void
    {
        $this->masukSebagai('admin');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));

        $page = $this->get('/admin/pendaftaran-ujian')
            ->assertOk()
            ->inertiaPage();
        $ringkasan = collect($page['props']['ringkasanProgramTes'])
            ->firstWhere('id', $slot->periodeUjian->ujian->program_tes_id);

        $this->assertNull($page['props']['programTesTerpilihId']);
        $this->assertNotNull($ringkasan);
        $this->assertGreaterThanOrEqual(1, $ringkasan['jumlahUjian']);
        $this->assertGreaterThanOrEqual(1, $ringkasan['jumlahPeriodePendaftaran']);
    }

    public function test_admin_dapat_membuka_daftar_periode_pendaftaran_per_program_tes(): void
    {
        $this->masukSebagai('admin');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));
        $programTesId = $slot->periodeUjian->ujian->program_tes_id;

        $page = $this->get('/admin/pendaftaran-ujian?program_tes_id='.$programTesId)
            ->assertOk()
            ->inertiaPage();

        $this->assertSame($programTesId, $page['props']['programTesTerpilihId']);
        $this->assertTrue(collect($page['props']['periodePendaftaranUjian']['data'])->every(
            fn (array $periode): bool => $periode['periode_ujian']['ujian']['program_tes_id'] === $programTesId,
        ));
        $this->assertTrue(collect($page['props']['periodeUjian'])->every(
            fn (array $periode): bool => $periode['ujian']['program_tes_id'] === $programTesId,
        ));
    }

    public function test_program_tes_dengan_ujian_aktif_tanpa_periode_pendaftaran_tetap_muncul(): void
    {
        $this->masukSebagai('admin');

        $programTes = ProgramTes::create([
            'kode' => '010',
            'nama' => 'Program Tes Tanpa Pendaftaran',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);
        Ujian::create([
            'program_tes_id' => $programTes->id,
            'judul' => 'Ujian Aktif Tanpa Pendaftaran',
            'mode' => ModeUjian::OFFICIAL->value,
            'durasi_menit' => 60,
            'aktif' => true,
        ]);

        $page = $this->get('/admin/pendaftaran-ujian')
            ->assertOk()
            ->inertiaPage();
        $ringkasan = collect($page['props']['ringkasanProgramTes'])
            ->firstWhere('id', $programTes->id);

        $this->assertNotNull($ringkasan);
        $this->assertSame(1, (int) $ringkasan['jumlahUjian']);
        $this->assertSame(0, (int) $ringkasan['jumlahPeriodePendaftaran']);

        $detail = $this->get('/admin/pendaftaran-ujian?program_tes_id='.$programTes->id)
            ->assertOk()
            ->inertiaPage();

        $this->assertSame($programTes->id, $detail['props']['programTesTerpilihId']);
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
            'pengguna_id' => Pengguna::where('email', 'peserta@toefl.test')->value('id'),
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
            'email' => 'unverified-peserta@toefl.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'aktif' => true,
        ]);
        $mahasiswa->assignRole('peserta');

        $this->post('/login', [
            'email' => $mahasiswa->email,
            'password' => 'password',
        ]);

        $this->get('/ujian/jadwal')->assertOk();
    }

    public function test_mahasiswa_dapat_menelusuri_jadwal_dengan_konteks_ujian(): void
    {
        $this->masukSebagai('peserta');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));
        $ujian = $slot->periodeUjian->ujian;

        $rootPage = $this->get('/ujian/jadwal')->assertOk()->inertiaPage();
        $this->assertTrue(collect($rootPage['props']['ringkasanProgramTes'])->contains('id', $ujian->program_tes_id));

        $this->get('/ujian/jadwal?program_tes_id='.$ujian->program_tes_id.'&ujian_id='.$ujian->id)
            ->assertOk();

        $periodPage = $this->get('/ujian/jadwal?program_tes_id='.$ujian->program_tes_id
            .'&ujian_id='.$ujian->id
            .'&periode_ujian_id='.$slot->periode_ujian_id)
            ->assertOk()
            ->inertiaPage();
        $this->assertSame($slot->periode_ujian_id, $periodPage['props']['periodeUjian'][0]['id']);
    }

    public function test_jadwal_menampilkan_jumlah_pendaftaran_yang_disetujui(): void
    {
        $mahasiswa = $this->masukSebagai('peserta');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));
        $pendaftaran = PendaftaranUjian::create([
            'slot_ujian_id' => $slot->id,
            'pengguna_id' => $mahasiswa->id,
            'status' => StatusPendaftaranUjian::APPROVED,
        ]);

        $page = $this->get('/ujian/jadwal?program_tes_id='.$slot->periodeUjian->ujian->program_tes_id
            .'&ujian_id='.$slot->periodeUjian->ujian_id
            .'&periode_ujian_id='.$slot->periode_ujian_id)
            ->assertOk()
            ->inertiaPage();

        $this->assertSame($pendaftaran->slot_ujian_id, $page['props']['periodeUjian'][0]['slot_ujian'][0]['id']);
        $this->assertSame(1, $page['props']['periodeUjian'][0]['slot_ujian'][0]['pendaftaran_disetujui_count']);
    }

    public function test_mahasiswa_mengajukan_pendaftaran_official_dengan_bukti_pembayaran(): void
    {
        $mahasiswa = $this->masukSebagai('peserta');
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

        $pendaftaran = PendaftaranUjian::where('slot_ujian_id', $slot->id)->firstOrFail();

        $this->assertNotNull($pendaftaran->lokasi_bukti_pembayaran);
        $this->assertStringEndsWith('.webp', $pendaftaran->lokasi_bukti_pembayaran);
        $this->assertTrue(Storage::disk('public')->exists($pendaftaran->lokasi_bukti_pembayaran));
        $this->assertLessThanOrEqual(
            2 * 1024 * 1024,
            Storage::disk('public')->size($pendaftaran->lokasi_bukti_pembayaran),
        );
    }

    public function test_pendaftaran_official_menolak_bukti_pembayaran_yang_tidak_diunggah(): void
    {
        $mahasiswa = $this->masukSebagai('peserta');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));

        $this->post('/ujian/slot/'.$slot->id.'/pendaftaran', [
            'nim' => $mahasiswa->profilMahasiswa->nim,
            'fakultas_id' => $mahasiswa->profilMahasiswa->fakultas_id,
            'program_studi_id' => $mahasiswa->profilMahasiswa->program_studi_id,
            'tahun_angkatan' => $mahasiswa->profilMahasiswa->tahun_angkatan,
        ])->assertSessionHasErrors('bukti_pembayaran');

        $this->assertDatabaseCount('pendaftaran_ujian', 0);
    }

    public function test_pendaftaran_official_menolak_bukti_pembayaran_lebih_dari_dua_mb(): void
    {
        $mahasiswa = $this->masukSebagai('peserta');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));

        $this->post('/ujian/slot/'.$slot->id.'/pendaftaran', [
            'nim' => $mahasiswa->profilMahasiswa->nim,
            'fakultas_id' => $mahasiswa->profilMahasiswa->fakultas_id,
            'program_studi_id' => $mahasiswa->profilMahasiswa->program_studi_id,
            'tahun_angkatan' => $mahasiswa->profilMahasiswa->tahun_angkatan,
            'bukti_pembayaran' => UploadedFile::fake()->image('bukti.png')->size(3000),
        ])->assertSessionHasErrors('bukti_pembayaran');

        $this->assertDatabaseCount('pendaftaran_ujian', 0);
    }

    public function test_pendaftaran_official_ditolak_setelah_periode_pendaftaran_ditutup(): void
    {
        $mahasiswa = $this->masukSebagai('peserta');
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
        $mahasiswa = $this->masukSebagai('peserta');
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
        $this->masukSebagai('peserta');
        $this->delete('/ujian/pendaftaran/'.$pendaftaran->id)->assertRedirect();

        $this->assertDatabaseHas('pendaftaran_ujian', [
            'id' => $pendaftaran->id,
            'status' => StatusPendaftaranUjian::CANCELLED->value,
        ]);
    }

    public function test_official_yang_menunggu_tidak_dapat_masuk_ke_pemeriksaan_awal(): void
    {
        $mahasiswa = $this->masukSebagai('peserta');
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
        $mahasiswa = $this->masukSebagai('peserta');
        $slot = $this->buatSlot(ModeUjian::TRYOUT->value, now()->subMinutes(5));
        $this->lengkapiSoalUjian($slot->periodeUjian->ujian);

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
        ]);

        $sesi = SesiUjian::where('slot_ujian_id', $slot->id)
            ->where('pengguna_id', $mahasiswa->id)
            ->firstOrFail();

        $this->assertNotNull($sesi->skema_penilaian_id);
        $this->assertNotNull($sesi->snapshot_penilaian);
        $this->assertSame('400.0', $sesi->skor_total_maksimum);
    }

    public function test_sesi_memakai_snapshot_walau_skema_aktif_berubah(): void
    {
        $mahasiswa = $this->masukSebagai('peserta');
        $slot = $this->buatSlot(ModeUjian::TRYOUT->value, now()->subMinutes(5));
        $ujian = $slot->periodeUjian->ujian;
        $this->lengkapiSoalUjian($ujian);

        $this->post('/ujian/slot/'.$slot->id.'/mulai', [
            'jenis_perangkat' => 'desktop',
            'agen_pengguna_perangkat' => 'PendaftaranUjianTest',
            'swafoto' => UploadedFile::fake()->image('selfie.jpg'),
        ])->assertRedirect();

        $sesi = SesiUjian::where('slot_ujian_id', $slot->id)
            ->where('pengguna_id', $mahasiswa->id)
            ->firstOrFail();

        $snapshotAwal = $sesi->snapshot_penilaian;
        $skemaAwalId = $sesi->skema_penilaian_id;

        $layanan = app(LayananAturanPenilaian::class);
        $programTes = ProgramTes::findOrFail($ujian->program_tes_id);
        $draft = $layanan->buatVersi($programTes);
        $reading = Section::where('program_tes_id', $programTes->id)
            ->where('nama', 'Reading')
            ->firstOrFail();

        $layanan->simpanAturan($draft, $reading, [
            'tabel_konversi' => array_fill(0, 51, 200),
            'skor_mentah_maksimum' => 50,
            'skor_skala_minimum' => 100,
            'skor_skala_maksimum' => 200,
        ]);
        $layanan->aktifkan($draft);

        $this->assertSame(
            $draft->id,
            SkemaPenilaian::where('program_tes_id', $programTes->id)
                ->where('status', 'aktif')
                ->value('id'),
        );

        $soalIds = Soal::whereHas('partUjian', fn ($query) => $query->where('ujian_id', $ujian->id))
            ->pluck('id');

        foreach ($soalIds as $soalId) {
            $this->postJson("/ujian/sesi/{$sesi->id}/jawaban", [
                'soal_id' => $soalId,
                'teks_jawaban' => 'A',
            ])->assertOk();
        }

        $this->post("/ujian/sesi/{$sesi->id}/serahkan")->assertRedirect(route('dasbor'));

        $sesi->refresh();

        $this->assertSame($skemaAwalId, $sesi->skema_penilaian_id);
        $this->assertSame($snapshotAwal, $sesi->snapshot_penilaian);
        $this->assertSame('204.0', $sesi->skor_total);
        $this->assertFalse($sesi->lulus);
    }
}
