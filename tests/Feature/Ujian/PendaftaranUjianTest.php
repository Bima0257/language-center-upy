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

    private function buatUjianTryout(string $lingkup = 'umum'): Ujian
    {
        $programTes = ProgramTes::firstOrFail();

        return Ujian::create([
            'program_tes_id' => $programTes->id,
            'judul' => 'Ujian Tryout '.strtoupper($lingkup),
            'mode' => ModeUjian::TRYOUT->value,
            'lingkup' => $lingkup,
            'durasi_menit' => 120,
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

    public function test_admin_dapat_crud_periode_pendaftaran_dan_menampilkan_ringkasannya(): void
    {
        $this->masukSebagai('admin');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));
        $periodePendaftaran = $slot->periodeUjian->periodePendaftaran;

        $rootPage = $this->get('/admin/pendaftaran-ujian?program_tes_id='.$slot->periodeUjian->ujian->program_tes_id)
            ->assertOk()
            ->inertiaPage();
        $this->assertArrayHasKey('periodeUjian', $rootPage['props']);
        $baris = collect($rootPage['props']['periodePendaftaranUjian']['data'])
            ->firstWhere('id', $periodePendaftaran->id);
        $this->assertNotNull($baris);
        $this->assertSame(1, $baris['slot_ujian_count']);
        $this->assertNotNull($baris['periode_ujian']['tanggal_mulai']);
        $this->assertArrayHasKey('catatan', $baris);

        $catatanHtml = '<p><strong>Catatan baru</strong> pembayaran</p>';

        $this->put('/admin/pendaftaran-ujian/'.$periodePendaftaran->id, [
            'nama' => 'Periode Pendaftaran Diperbarui',
            'dibuka_pada' => now()->subDay()->format('Y-m-d H:i:s'),
            'ditutup_pada' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'aktif' => true,
            'catatan' => $catatanHtml,
        ])->assertRedirect();
        $this->assertDatabaseHas('periode_pendaftaran_ujian', [
            'id' => $periodePendaftaran->id,
            'nama' => 'Periode Pendaftaran Diperbarui',
            'catatan' => $catatanHtml,
        ]);

        $detailPage = $this->get('/admin/pendaftaran-ujian?program_tes_id='.$slot->periodeUjian->ujian->program_tes_id)
            ->assertOk()
            ->inertiaPage();
        $barisDiperbarui = collect($detailPage['props']['periodePendaftaranUjian']['data'])
            ->firstWhere('id', $periodePendaftaran->id);
        $this->assertSame($catatanHtml, $barisDiperbarui['catatan']);

        $this->delete('/admin/pendaftaran-ujian/'.$periodePendaftaran->id)->assertRedirect();
        $this->assertDatabaseMissing('periode_pendaftaran_ujian', ['id' => $periodePendaftaran->id]);
    }

    public function test_halaman_legacy_periode_pendaftaran_tidak_tersedia(): void
    {
        $this->masukSebagai('admin');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));
        $periodePendaftaran = $slot->periodeUjian->periodePendaftaran;

        $this->get('/admin/pendaftaran-ujian/tambah')->assertNotFound();
        $this->get('/admin/pendaftaran-ujian/'.$periodePendaftaran->id.'/ubah')->assertNotFound();
        $this->get('/admin/pendaftaran-ujian/'.$periodePendaftaran->id)->assertMethodNotAllowed();
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

    public function test_admin_dapat_membuka_ringkasan_peserta_per_ujian(): void
    {
        $this->masukSebagai('admin');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));
        $mahasiswa = Pengguna::where('email', 'peserta@toefl.test')->firstOrFail();

        PendaftaranUjian::create([
            'slot_ujian_id' => $slot->id,
            'pengguna_id' => $mahasiswa->id,
            'status' => StatusPendaftaranUjian::PENDING,
        ]);

        $page = $this->get('/admin/pendaftaran-ujian/peserta')
            ->assertOk()
            ->inertiaPage();

        $baris = collect($page['props']['ringkasanUjian']['data'])
            ->firstWhere('id', $slot->periodeUjian->ujian_id);

        $this->assertSame('Admin/PendaftaranUjian/peserta-semua', $page['component']);
        $this->assertNotNull($baris);
        $this->assertSame(1, $baris['jumlahPendaftaran']);
        $this->assertSame(1, $baris['pendingCount']);
        $this->assertSame(0, $baris['approvedCount']);
        $this->assertNotEmpty($page['props']['programTes']);
    }

    public function test_ringkasan_peserta_hanya_menampilkan_ujian_yang_memiliki_pendaftaran(): void
    {
        $this->masukSebagai('admin');
        $slotDenganPendaftaran = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));
        $slotTanpaPendaftaran = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDays(2)->setTime(8, 0));
        $mahasiswa = Pengguna::where('email', 'peserta@toefl.test')->firstOrFail();

        PendaftaranUjian::create([
            'slot_ujian_id' => $slotDenganPendaftaran->id,
            'pengguna_id' => $mahasiswa->id,
            'status' => StatusPendaftaranUjian::PENDING,
        ]);

        $page = $this->get('/admin/pendaftaran-ujian/peserta')
            ->assertOk()
            ->inertiaPage();

        $ids = collect($page['props']['ringkasanUjian']['data'])->pluck('id');

        $this->assertTrue($ids->contains($slotDenganPendaftaran->periodeUjian->ujian_id));
        $this->assertFalse($ids->contains($slotTanpaPendaftaran->periodeUjian->ujian_id));
    }

    public function test_ringkasan_peserta_mendukung_filter_approval_program_tes_dan_pencarian(): void
    {
        $this->masukSebagai('admin');
        $slotPending = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));
        $slotApproved = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDays(2)->setTime(8, 0));
        $mahasiswa = Pengguna::where('email', 'peserta@toefl.test')->firstOrFail();
        $programTesId = $slotPending->periodeUjian->ujian->program_tes_id;

        PendaftaranUjian::create([
            'slot_ujian_id' => $slotPending->id,
            'pengguna_id' => $mahasiswa->id,
            'status' => StatusPendaftaranUjian::PENDING,
        ]);
        PendaftaranUjian::create([
            'slot_ujian_id' => $slotApproved->id,
            'pengguna_id' => $mahasiswa->id,
            'status' => StatusPendaftaranUjian::APPROVED,
        ]);

        $page = $this->get('/admin/pendaftaran-ujian/peserta'
            .'?f_status=pending'
            .'&f_program_tes_id='.$programTesId
            .'&q='.urlencode('Ujian Pendaftaran')
            .'&per_page=25')
            ->assertOk()
            ->inertiaPage();

        $ids = collect($page['props']['ringkasanUjian']['data'])->pluck('id');

        $this->assertTrue($ids->contains($slotPending->periodeUjian->ujian_id));
        $this->assertFalse($ids->contains($slotApproved->periodeUjian->ujian_id));
        $this->assertSame('pending', $page['props']['filters']['status']);
        $this->assertSame($programTesId, $page['props']['filters']['program_tes_id']);
        $this->assertSame(25, $page['props']['ringkasanUjian']['per_page']);
    }

    public function test_admin_dapat_membuka_daftar_peserta_per_ujian(): void
    {
        $this->masukSebagai('admin');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));
        $ujian = $slot->periodeUjian->ujian;
        $mahasiswa = Pengguna::where('email', 'peserta@toefl.test')->firstOrFail();
        $dosen = Pengguna::where('email', 'dosen@toefl.test')->firstOrFail();

        $pendaftaranPending = PendaftaranUjian::create([
            'slot_ujian_id' => $slot->id,
            'pengguna_id' => $mahasiswa->id,
            'status' => StatusPendaftaranUjian::PENDING,
        ]);
        $pendaftaranApproved = PendaftaranUjian::create([
            'slot_ujian_id' => $slot->id,
            'pengguna_id' => $dosen->id,
            'status' => StatusPendaftaranUjian::APPROVED,
        ]);

        $page = $this->get('/admin/pendaftaran-ujian/peserta?ujian_id='.$ujian->id
            .'&f_status=pending&q='.urlencode($mahasiswa->name).'&per_page=15')
            ->assertOk()
            ->inertiaPage();

        $ids = collect($page['props']['pendaftaranUjian']['data'])->pluck('id');

        $this->assertSame('Admin/PendaftaranUjian/peserta-ujian', $page['component']);
        $this->assertSame($ujian->id, $page['props']['ujianTerpilih']['id']);
        $this->assertSame('pending', $page['props']['filters']['status']);
        $this->assertSame($ujian->id, $page['props']['filters']['ujian_id']);
        $this->assertTrue($ids->contains($pendaftaranPending->id));
        $this->assertFalse($ids->contains($pendaftaranApproved->id));
        $this->assertSame(15, $page['props']['pendaftaranUjian']['per_page']);
    }

    public function test_peserta_tidak_dapat_membuka_daftar_semua_peserta_pendaftaran(): void
    {
        $this->masukSebagai('peserta');

        $this->get('/admin/pendaftaran-ujian/peserta')->assertForbidden();
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

    public function test_peserta_dapat_membuka_halaman_pendaftaran_ujian(): void
    {
        $this->masukSebagai('peserta');

        $page = $this->get('/ujian/pendaftaran')->assertOk()->inertiaPage();

        $this->assertSame('Ujian/Pendaftaran/index', $page['component']);
        $this->assertArrayHasKey('periodeUjian', $page['props']);
        $this->assertArrayHasKey('pendaftaranSaya', $page['props']);
        $this->assertArrayHasKey('profil', $page['props']);
        $this->assertArrayHasKey('fakultas', $page['props']);
        $this->assertArrayHasKey('programStudi', $page['props']);
    }

    public function test_tamu_diarahkan_ke_login_saat_membuka_halaman_pendaftaran_ujian(): void
    {
        $this->get('/ujian/pendaftaran')->assertRedirect('/login');
    }

    public function test_admin_tidak_dapat_membuka_halaman_pendaftaran_ujian(): void
    {
        $this->masukSebagai('admin');

        $this->get('/ujian/pendaftaran')->assertForbidden();
    }

    public function test_halaman_pendaftaran_menampilkan_slot_tersedia_dan_status_pendaftaran_peserta(): void
    {
        $this->masukSebagai('peserta');

        $page = $this->get('/ujian/pendaftaran')->assertOk()->inertiaPage();
        $slots = collect($page['props']['periodeUjian'])
            ->flatMap(fn (array $periode): array => $periode['slot_ujian'] ?? []);

        $this->assertTrue($slots->contains(fn (array $slot): bool => $slot['pendaftaran_terbuka'] === true
            && $slot['pendaftaran_status'] === null));
        $this->assertTrue($slots->contains(fn (array $slot): bool => $slot['pendaftaran_status'] === 'approved'));

        $riwayat = collect($page['props']['pendaftaranSaya']);
        $this->assertTrue($riwayat->contains(fn (array $pendaftaran): bool => $pendaftaran['status'] === 'approved'));
    }

    public function test_halaman_pendaftaran_menampilkan_riwayat_di_luar_periode_aktif(): void
    {
        $mahasiswa = $this->masukSebagai('peserta');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->subDays(2)->setTime(8, 0));
        $slot->periodeUjian->update([
            'tanggal_selesai' => now()->subDay()->toDateString(),
        ]);

        $pendaftaran = PendaftaranUjian::create([
            'slot_ujian_id' => $slot->id,
            'pengguna_id' => $mahasiswa->id,
            'status' => StatusPendaftaranUjian::APPROVED,
        ]);

        $page = $this->get('/ujian/pendaftaran')->assertOk()->inertiaPage();
        $riwayat = collect($page['props']['pendaftaranSaya']);

        $this->assertTrue(
            $riwayat->contains(fn (array $item): bool => $item['id'] === $pendaftaran->id),
        );
        $this->assertFalse(
            collect($page['props']['periodeUjian'])->pluck('id')->contains($slot->periode_ujian_id),
        );
    }

    public function test_mahasiswa_mengajukan_pendaftaran_official_dengan_bukti_pembayaran(): void
    {
        $mahasiswa = $this->masukSebagai('peserta');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));

        $this->post('/ujian/slot/'.$slot->id.'/pendaftaran', [
            'nomor_identitas' => '2200099999',
            'fakultas_id' => $mahasiswa->profilPeserta->fakultas_id,
            'program_studi_id' => $mahasiswa->profilPeserta->program_studi_id,
            'tahun_mulai' => 2022,
            'bukti_pembayaran' => UploadedFile::fake()->image('bukti.png'),
        ])->assertRedirect();

        $this->assertDatabaseHas('pendaftaran_ujian', [
            'slot_ujian_id' => $slot->id,
            'pengguna_id' => $mahasiswa->id,
            'status' => StatusPendaftaranUjian::PENDING->value,
        ]);
        $this->assertDatabaseHas('profil_peserta', [
            'pengguna_id' => $mahasiswa->id,
            'nomor_identitas' => '2200099999',
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
            'nomor_identitas' => $mahasiswa->profilPeserta->nomor_identitas,
            'fakultas_id' => $mahasiswa->profilPeserta->fakultas_id,
            'program_studi_id' => $mahasiswa->profilPeserta->program_studi_id,
            'tahun_mulai' => $mahasiswa->profilPeserta->tahun_mulai,
        ])->assertSessionHasErrors('bukti_pembayaran');

        $this->assertDatabaseMissing('pendaftaran_ujian', ['slot_ujian_id' => $slot->id]);
    }

    public function test_pendaftaran_official_menolak_bukti_pembayaran_lebih_dari_dua_mb(): void
    {
        $mahasiswa = $this->masukSebagai('peserta');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));

        $this->post('/ujian/slot/'.$slot->id.'/pendaftaran', [
            'nomor_identitas' => $mahasiswa->profilPeserta->nomor_identitas,
            'fakultas_id' => $mahasiswa->profilPeserta->fakultas_id,
            'program_studi_id' => $mahasiswa->profilPeserta->program_studi_id,
            'tahun_mulai' => $mahasiswa->profilPeserta->tahun_mulai,
            'bukti_pembayaran' => UploadedFile::fake()->image('bukti.png')->size(3000),
        ])->assertSessionHasErrors('bukti_pembayaran');

        $this->assertDatabaseMissing('pendaftaran_ujian', ['slot_ujian_id' => $slot->id]);
    }

    public function test_pendaftaran_official_ditolak_setelah_periode_pendaftaran_ditutup(): void
    {
        $mahasiswa = $this->masukSebagai('peserta');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));
        $slot->periodeUjian->periodePendaftaran->update([
            'ditutup_pada' => now()->subMinute(),
        ]);

        $this->post('/ujian/slot/'.$slot->id.'/pendaftaran', [
            'nomor_identitas' => $mahasiswa->profilPeserta->nomor_identitas,
            'fakultas_id' => $mahasiswa->profilPeserta->fakultas_id,
            'program_studi_id' => $mahasiswa->profilPeserta->program_studi_id,
            'tahun_mulai' => $mahasiswa->profilPeserta->tahun_mulai,
            'bukti_pembayaran' => UploadedFile::fake()->image('bukti.png'),
        ])->assertRedirect()->assertSessionHas('error', 'Pendaftaran ujian sudah ditutup.');

        $this->assertDatabaseMissing('pendaftaran_ujian', ['slot_ujian_id' => $slot->id]);
    }

    public function test_admin_dapat_menyetujui_pendaftaran_dan_mahasiswa_dapat_membatalkannya(): void
    {
        $mahasiswa = $this->masukSebagai('peserta');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));
        $this->post('/ujian/slot/'.$slot->id.'/pendaftaran', [
            'nomor_identitas' => $mahasiswa->profilPeserta->nomor_identitas,
            'fakultas_id' => $mahasiswa->profilPeserta->fakultas_id,
            'program_studi_id' => $mahasiswa->profilPeserta->program_studi_id,
            'tahun_mulai' => $mahasiswa->profilPeserta->tahun_mulai,
            'bukti_pembayaran' => UploadedFile::fake()->image('bukti.png'),
        ])->assertRedirect();

        $pendaftaran = PendaftaranUjian::where('slot_ujian_id', $slot->id)
            ->where('pengguna_id', $mahasiswa->id)
            ->firstOrFail();
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

        $page = $this->get('/ujian/pendaftaran')->assertOk()->inertiaPage();
        $riwayat = collect($page['props']['pendaftaranSaya']);

        $this->assertFalse(
            $riwayat->contains(fn (array $item): bool => $item['id'] === $pendaftaran->id),
        );
    }

    public function test_mahasiswa_dapat_mengajukan_ulang_setelah_membatalkan_pendaftaran(): void
    {
        $mahasiswa = $this->masukSebagai('peserta');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));

        $this->post('/ujian/slot/'.$slot->id.'/pendaftaran', [
            'nomor_identitas' => $mahasiswa->profilPeserta->nomor_identitas,
            'fakultas_id' => $mahasiswa->profilPeserta->fakultas_id,
            'program_studi_id' => $mahasiswa->profilPeserta->program_studi_id,
            'tahun_mulai' => $mahasiswa->profilPeserta->tahun_mulai,
            'bukti_pembayaran' => UploadedFile::fake()->image('bukti.png'),
        ])->assertRedirect();

        $pendaftaran = PendaftaranUjian::where('slot_ujian_id', $slot->id)
            ->where('pengguna_id', $mahasiswa->id)
            ->firstOrFail();

        $this->delete('/ujian/pendaftaran/'.$pendaftaran->id)->assertRedirect();
        $this->assertDatabaseHas('pendaftaran_ujian', [
            'id' => $pendaftaran->id,
            'status' => StatusPendaftaranUjian::CANCELLED->value,
        ]);

        $this->post('/ujian/slot/'.$slot->id.'/pendaftaran', [
            'nomor_identitas' => $mahasiswa->profilPeserta->nomor_identitas,
            'fakultas_id' => $mahasiswa->profilPeserta->fakultas_id,
            'program_studi_id' => $mahasiswa->profilPeserta->program_studi_id,
            'tahun_mulai' => $mahasiswa->profilPeserta->tahun_mulai,
            'bukti_pembayaran' => UploadedFile::fake()->image('bukti-ulang.png'),
        ])->assertRedirect();

        $pendaftaranUlang = PendaftaranUjian::where('slot_ujian_id', $slot->id)
            ->where('pengguna_id', $mahasiswa->id)
            ->firstOrFail();

        $this->assertSame($pendaftaran->id, $pendaftaranUlang->id);
        $this->assertSame(StatusPendaftaranUjian::PENDING, $pendaftaranUlang->status);
        $this->assertSame(1, PendaftaranUjian::where('slot_ujian_id', $slot->id)
            ->where('pengguna_id', $mahasiswa->id)
            ->count());

        $page = $this->get('/ujian/pendaftaran')->assertOk()->inertiaPage();
        $riwayat = collect($page['props']['pendaftaranSaya']);

        $this->assertTrue(
            $riwayat->contains(fn (array $item): bool => $item['id'] === $pendaftaranUlang->id),
        );
    }

    public function test_riwayat_pendaftaran_tidak_menampilkan_status_dibatalkan(): void
    {
        $mahasiswa = $this->masukSebagai('peserta');
        $slotTampil = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));
        $slotBatal = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDays(2)->setTime(8, 0));

        $pendaftaranTampil = PendaftaranUjian::create([
            'slot_ujian_id' => $slotTampil->id,
            'pengguna_id' => $mahasiswa->id,
            'status' => StatusPendaftaranUjian::APPROVED,
        ]);
        $pendaftaranBatal = PendaftaranUjian::create([
            'slot_ujian_id' => $slotBatal->id,
            'pengguna_id' => $mahasiswa->id,
            'status' => StatusPendaftaranUjian::CANCELLED,
        ]);

        $page = $this->get('/ujian/pendaftaran')->assertOk()->inertiaPage();
        $riwayat = collect($page['props']['pendaftaranSaya']);

        $this->assertTrue(
            $riwayat->contains(fn (array $item): bool => $item['id'] === $pendaftaranTampil->id),
        );
        $this->assertFalse(
            $riwayat->contains(fn (array $item): bool => $item['id'] === $pendaftaranBatal->id),
        );
    }

    public function test_official_yang_menunggu_tidak_dapat_masuk_ke_pemeriksaan_awal(): void
    {
        $mahasiswa = $this->masukSebagai('peserta');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));

        $this->post('/ujian/slot/'.$slot->id.'/pendaftaran', [
            'nomor_identitas' => $mahasiswa->profilPeserta->nomor_identitas,
            'fakultas_id' => $mahasiswa->profilPeserta->fakultas_id,
            'program_studi_id' => $mahasiswa->profilPeserta->program_studi_id,
            'tahun_mulai' => $mahasiswa->profilPeserta->tahun_mulai,
            'bukti_pembayaran' => UploadedFile::fake()->image('bukti.png'),
        ])->assertRedirect();

        $this->get('/ujian/slot/'.$slot->id.'/pemeriksaan-awal')
            ->assertRedirect(route('ujian.jadwal'))
            ->assertSessionHas('error', 'Pendaftaran ujian Anda belum disetujui.');
    }

    public function test_dosen_dapat_mengajukan_pendaftaran_official(): void
    {
        $dosen = $this->masukSebagai('dosen');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));

        $this->post('/ujian/slot/'.$slot->id.'/pendaftaran', [
            'nomor_identitas' => 'NIDN-2200010002',
            'fakultas_id' => $dosen->profilPeserta->fakultas_id,
            'program_studi_id' => $dosen->profilPeserta->program_studi_id,
            'bukti_pembayaran' => UploadedFile::fake()->image('bukti.png'),
        ])->assertRedirect();

        $this->assertDatabaseHas('pendaftaran_ujian', [
            'slot_ujian_id' => $slot->id,
            'pengguna_id' => $dosen->id,
            'status' => StatusPendaftaranUjian::PENDING->value,
        ]);
    }

    public function test_pendaftaran_dosen_mewajibkan_fakultas_dan_prodi(): void
    {
        $this->masukSebagai('dosen');
        $slot = $this->buatSlot(ModeUjian::OFFICIAL->value, now()->addDay()->setTime(8, 0));

        $this->post('/ujian/slot/'.$slot->id.'/pendaftaran', [
            'nomor_identitas' => 'NIDN-2200010002',
            'bukti_pembayaran' => UploadedFile::fake()->image('bukti.png'),
        ])->assertSessionHasErrors(['fakultas_id', 'program_studi_id']);

        $this->assertDatabaseMissing('pendaftaran_ujian', ['slot_ujian_id' => $slot->id]);
    }

    public function test_umum_dapat_memulai_tryout(): void
    {
        $umum = $this->masukSebagai('umum');
        $ujian = $this->buatUjianTryout();
        $this->lengkapiSoalUjian($ujian);

        $this->post('/ujian/tryout/'.$ujian->id.'/mulai')->assertRedirect();

        $this->assertDatabaseMissing('pendaftaran_ujian', [
            'pengguna_id' => $umum->id,
        ]);
        $this->assertDatabaseHas('sesi_ujian', [
            'ujian_id' => $ujian->id,
            'slot_ujian_id' => null,
            'pengguna_id' => $umum->id,
        ]);
    }

    public function test_tryout_tidak_membuat_pendaftaran_saat_sesi_dimulai(): void
    {
        $mahasiswa = $this->masukSebagai('peserta');
        $ujian = $this->buatUjianTryout();
        $this->lengkapiSoalUjian($ujian);
        $jumlahPendaftaranAwal = PendaftaranUjian::where('pengguna_id', $mahasiswa->id)->count();

        $this->post('/ujian/tryout/'.$ujian->id.'/mulai')->assertRedirect();

        $this->assertSame(
            $jumlahPendaftaranAwal,
            PendaftaranUjian::where('pengguna_id', $mahasiswa->id)->count(),
        );
        $this->assertDatabaseHas('sesi_ujian', [
            'ujian_id' => $ujian->id,
            'slot_ujian_id' => null,
            'pengguna_id' => $mahasiswa->id,
        ]);

        $sesi = SesiUjian::where('ujian_id', $ujian->id)
            ->where('pengguna_id', $mahasiswa->id)
            ->firstOrFail();

        $this->assertNotNull($sesi->skema_penilaian_id);
        $this->assertNotNull($sesi->snapshot_penilaian);
        $this->assertSame('400.0', $sesi->skor_total_maksimum);
    }

    public function test_sesi_memakai_snapshot_walau_skema_diubah(): void
    {
        $mahasiswa = $this->masukSebagai('peserta');
        $ujian = $this->buatUjianTryout();
        $this->lengkapiSoalUjian($ujian);

        $this->post('/ujian/tryout/'.$ujian->id.'/mulai')->assertRedirect();

        $sesi = SesiUjian::where('ujian_id', $ujian->id)
            ->where('pengguna_id', $mahasiswa->id)
            ->firstOrFail();

        $snapshotAwal = $sesi->snapshot_penilaian;
        $skemaAwalId = $sesi->skema_penilaian_id;

        $layanan = app(LayananAturanPenilaian::class);
        $programTes = ProgramTes::findOrFail($ujian->program_tes_id);
        $skema = $layanan->temukanSkema($skemaAwalId);
        $reading = Section::where('program_tes_id', $programTes->id)
            ->where('nama', 'Reading')
            ->firstOrFail();

        $ujianAktifIds = Ujian::where('program_tes_id', $programTes->id)
            ->where('aktif', true)
            ->pluck('id')
            ->all();

        Ujian::whereIn('id', $ujianAktifIds)->update(['aktif' => false]);

        $layanan->simpanAturan($skema, $reading, [
            'tabel_konversi' => array_fill(0, 51, 200),
            'skor_mentah_maksimum' => 50,
            'skor_skala_minimum' => 100,
            'skor_skala_maksimum' => 200,
        ]);

        Ujian::whereIn('id', $ujianAktifIds)->update(['aktif' => true]);

        $partUjian = PartUjian::where('ujian_id', $ujian->id)
            ->orderBy('urutan')
            ->get();

        foreach ($partUjian as $index => $part) {
            $soalIds = $part->soalPartUjian()->pluck('soal_id');

            foreach ($soalIds as $soalId) {
                $this->postJson("/ujian/sesi/{$sesi->id}/jawaban", [
                    'soal_id' => $soalId,
                    'teks_jawaban' => 'A',
                ])->assertOk();
            }

            if ($index < $partUjian->count() - 1) {
                $this->postJson("/ujian/sesi/{$sesi->id}/selesaikan-section", [
                    'part_ujian_id' => $part->id,
                ])->assertOk();
            }
        }

        $this->post("/ujian/sesi/{$sesi->id}/serahkan")->assertRedirect(route('dasbor'));

        $sesi->refresh();

        $this->assertSame($skemaAwalId, $sesi->skema_penilaian_id);
        $this->assertSame($snapshotAwal, $sesi->snapshot_penilaian);
        $this->assertSame('204.0', $sesi->skor_total);
        $this->assertFalse($sesi->lulus);
    }
}
