<?php

namespace Tests\Feature\Ujian;

use App\Enums\LingkupUjian;
use App\Enums\ModeUjian;
use App\Enums\StatusSesi;
use App\Models\BankSoal;
use App\Models\PartUjian;
use App\Models\Pengguna;
use App\Models\PeriodePendaftaranUjian;
use App\Models\PeriodeUjian;
use App\Models\ProgramStudi;
use App\Models\ProgramTes;
use App\Models\Section;
use App\Models\SesiUjian;
use App\Models\SlotUjian;
use App\Models\Soal;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LingkupUjianTest extends TestCase
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

    private function prodiLain(): ProgramStudi
    {
        return ProgramStudi::where('kode', 'TI')->firstOrFail();
    }

    private function buatUjian(string $mode, string $lingkup = 'umum', array $programStudiIds = []): Ujian
    {
        $ujian = Ujian::create([
            'program_tes_id' => ProgramTes::where('nama', 'EPT')->firstOrFail()->id,
            'judul' => 'Ujian '.strtoupper($mode).' '.strtoupper($lingkup).' '.uniqid(),
            'mode' => $mode,
            'lingkup' => $lingkup,
            'durasi_menit' => 120,
            'aktif' => true,
        ]);

        if ($programStudiIds !== []) {
            $ujian->programStudi()->sync($programStudiIds);
        }

        return $ujian;
    }

    private function buatPeriodeResmi(Ujian $ujian): void
    {
        $periode = PeriodeUjian::create([
            'ujian_id' => $ujian->id,
            'judul' => 'Periode Resmi '.$ujian->judul,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addDays(2)->toDateString(),
            'aktif' => true,
        ]);

        PeriodePendaftaranUjian::create([
            'periode_ujian_id' => $periode->id,
            'nama' => 'Pendaftaran '.$ujian->judul,
            'dibuka_pada' => now()->subDay(),
            'ditutup_pada' => now()->addDays(2),
            'aktif' => true,
        ]);

        SlotUjian::create([
            'periode_ujian_id' => $periode->id,
            'tanggal' => now()->addDay()->toDateString(),
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '10:00',
            'toleransi_keterlambatan_menit' => 15,
            'kapasitas_peserta' => 30,
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

    public function test_tersedia_menampilkan_umum_dan_prodi_peserta(): void
    {
        $peserta = $this->masukSebagai('peserta');
        $prodiPeserta = ProgramStudi::findOrFail($peserta->profilPeserta->program_studi_id);
        $prodiLain = $this->prodiLain();

        $umum = $this->buatUjian(ModeUjian::TRYOUT->value);
        $prodiSendiri = $this->buatUjian(ModeUjian::TRYOUT->value, LingkupUjian::PRODI->value, [$prodiPeserta->id]);
        $prodiLainUjian = $this->buatUjian(ModeUjian::TRYOUT->value, LingkupUjian::PRODI->value, [$prodiLain->id]);

        $page = $this->get('/ujian/tersedia')->assertOk()->inertiaPage();
        $ids = collect($page['props']['ujian'])->pluck('id')->all();

        $this->assertContains($umum->id, $ids);
        $this->assertContains($prodiSendiri->id, $ids);
        $this->assertNotContains($prodiLainUjian->id, $ids);
    }

    public function test_peserta_umum_hanya_melihat_tryout_lingkup_umum(): void
    {
        $this->masukSebagai('umum');
        $prodi = $this->prodiLain();

        $umum = $this->buatUjian(ModeUjian::TRYOUT->value);
        $prodiUjian = $this->buatUjian(ModeUjian::TRYOUT->value, LingkupUjian::PRODI->value, [$prodi->id]);

        $page = $this->get('/ujian/tersedia')->assertOk()->inertiaPage();
        $ids = collect($page['props']['ujian'])->pluck('id')->all();

        $this->assertContains($umum->id, $ids);
        $this->assertNotContains($prodiUjian->id, $ids);
    }

    public function test_jadwal_peserta_hanya_menampilkan_ujian_resmi_sesuai_lingkup(): void
    {
        $prodiLain = $this->prodiLain();
        $prodiPeserta = ProgramStudi::where('kode', 'PBI')->firstOrFail();

        $ujianProdiLain = $this->buatUjian(ModeUjian::OFFICIAL->value, LingkupUjian::PRODI->value, [$prodiLain->id]);
        $this->buatPeriodeResmi($ujianProdiLain);

        $ujianProdiPeserta = $this->buatUjian(ModeUjian::OFFICIAL->value, LingkupUjian::PRODI->value, [$prodiPeserta->id]);
        $this->buatPeriodeResmi($ujianProdiPeserta);

        $this->masukSebagai('umum');
        $halamanUmum = $this->get('/ujian/jadwal')->assertOk()->inertiaPage();
        $idsUmum = collect($halamanUmum['props']['ringkasanUjian'])->pluck('id');

        $this->assertNotContains($ujianProdiLain->id, $idsUmum);
        $this->assertNotContains($ujianProdiPeserta->id, $idsUmum);

        $this->post('/logout');
        $this->masukSebagai('peserta');
        $halamanPeserta = $this->get('/ujian/jadwal')->assertOk()->inertiaPage();
        $idsPeserta = collect($halamanPeserta['props']['ringkasanUjian'])->pluck('id');

        $this->assertContains($ujianProdiPeserta->id, $idsPeserta);
        $this->assertNotContains($ujianProdiLain->id, $idsPeserta);
    }

    public function test_halaman_pendaftaran_peserta_hanya_menampilkan_ujian_resmi_sesuai_lingkup(): void
    {
        $prodiLain = $this->prodiLain();
        $prodiPeserta = ProgramStudi::where('kode', 'PBI')->firstOrFail();

        $ujianProdiLain = $this->buatUjian(ModeUjian::OFFICIAL->value, LingkupUjian::PRODI->value, [$prodiLain->id]);
        $this->buatPeriodeResmi($ujianProdiLain);

        $ujianProdiPeserta = $this->buatUjian(ModeUjian::OFFICIAL->value, LingkupUjian::PRODI->value, [$prodiPeserta->id]);
        $this->buatPeriodeResmi($ujianProdiPeserta);

        $this->masukSebagai('umum');
        $halamanUmum = $this->get('/ujian/pendaftaran')->assertOk()->inertiaPage();
        $idsUmum = collect($halamanUmum['props']['periodeUjian'])->pluck('ujian_id');

        $this->assertNotContains($ujianProdiLain->id, $idsUmum);
        $this->assertNotContains($ujianProdiPeserta->id, $idsUmum);

        $this->post('/logout');
        $this->masukSebagai('peserta');
        $halamanPeserta = $this->get('/ujian/pendaftaran')->assertOk()->inertiaPage();
        $idsPeserta = collect($halamanPeserta['props']['periodeUjian'])->pluck('ujian_id');

        $this->assertContains($ujianProdiPeserta->id, $idsPeserta);
        $this->assertNotContains($ujianProdiLain->id, $idsPeserta);
    }

    public function test_tryout_prodi_lain_ditolak_saat_mulai_langsung(): void
    {
        $peserta = $this->masukSebagai('peserta');
        $prodiLain = $this->prodiLain();
        $ujian = $this->buatUjian(ModeUjian::TRYOUT->value, LingkupUjian::PRODI->value, [$prodiLain->id]);
        $this->lengkapiSoalUjian($ujian);

        $this->post('/ujian/tryout/'.$ujian->id.'/mulai')
            ->assertSessionHas('error', 'Ujian ini tidak tersedia untuk program studi Anda.');

        $this->assertDatabaseMissing('sesi_ujian', [
            'ujian_id' => $ujian->id,
            'pengguna_id' => $peserta->id,
        ]);
    }

    public function test_tryout_resume_sesi_aktif_dan_boleh_mengulang_setelah_selesai(): void
    {
        $peserta = $this->masukSebagai('peserta');
        $ujian = $this->buatUjian(ModeUjian::TRYOUT->value);
        $this->lengkapiSoalUjian($ujian);

        $this->post('/ujian/tryout/'.$ujian->id.'/mulai')->assertRedirect();
        $this->post('/ujian/tryout/'.$ujian->id.'/mulai')->assertRedirect();

        $this->assertSame(1, SesiUjian::where('ujian_id', $ujian->id)->where('pengguna_id', $peserta->id)->count());

        SesiUjian::where('ujian_id', $ujian->id)
            ->where('pengguna_id', $peserta->id)
            ->update(['status' => StatusSesi::SUBMITTED->value, 'diserahkan_pada' => now()]);

        $this->post('/ujian/tryout/'.$ujian->id.'/mulai')->assertRedirect();

        $this->assertSame(2, SesiUjian::where('ujian_id', $ujian->id)->where('pengguna_id', $peserta->id)->count());
    }

    public function test_lingkup_dan_mode_terkunci_setelah_ada_sesi(): void
    {
        $peserta = $this->masukSebagai('peserta');
        $ujian = $this->buatUjian(ModeUjian::TRYOUT->value);
        $this->lengkapiSoalUjian($ujian);
        $this->post('/ujian/tryout/'.$ujian->id.'/mulai')->assertRedirect();

        $this->post('/logout');
        $this->masukSebagai('admin');

        $this->put('/admin/ujian/'.$ujian->id, [
            'program_tes_id' => $ujian->program_tes_id,
            'judul' => $ujian->judul,
            'mode' => ModeUjian::OFFICIAL->value,
            'lingkup' => LingkupUjian::UMUM->value,
            'durasi_menit' => 120,
            'aktif' => true,
        ])->assertSessionHasErrors('mode');

        $this->assertDatabaseHas('ujian', [
            'id' => $ujian->id,
            'mode' => ModeUjian::TRYOUT->value,
        ]);
    }

    public function test_prodi_peserta_terkunci_setelah_memulai_ujian(): void
    {
        $peserta = $this->masukSebagai('peserta');
        $prodiLain = $this->prodiLain();
        $ujian = $this->buatUjian(ModeUjian::TRYOUT->value);
        $this->lengkapiSoalUjian($ujian);
        $this->post('/ujian/tryout/'.$ujian->id.'/mulai')->assertRedirect();

        $this->post('/onboarding/upload-identity', [
            'nomor_identitas' => $peserta->profilPeserta->nomor_identitas,
            'fakultas_id' => $prodiLain->fakultas_id,
            'program_studi_id' => $prodiLain->id,
            'tahun_mulai' => $peserta->profilPeserta->tahun_mulai,
        ])->assertSessionHasErrors('program_studi_id');

        $this->assertDatabaseHas('profil_peserta', [
            'pengguna_id' => $peserta->id,
            'program_studi_id' => $peserta->profilPeserta->program_studi_id,
        ]);
    }

    public function test_ujian_resmi_prodi_lain_tidak_tampil_dan_tidak_bisa_didaftar(): void
    {
        $this->masukSebagai('peserta');
        $prodiLain = $this->prodiLain();
        $ujian = $this->buatUjian(ModeUjian::OFFICIAL->value, LingkupUjian::PRODI->value, [$prodiLain->id]);

        $periode = PeriodeUjian::create([
            'ujian_id' => $ujian->id,
            'judul' => 'Periode Resmi Prodi Lain',
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addDay()->toDateString(),
            'aktif' => true,
        ]);

        PeriodePendaftaranUjian::create([
            'periode_ujian_id' => $periode->id,
            'nama' => 'Pendaftaran Prodi Lain',
            'dibuka_pada' => now()->subDay(),
            'ditutup_pada' => now()->addDay(),
            'aktif' => true,
        ]);

        $slot = SlotUjian::create([
            'periode_ujian_id' => $periode->id,
            'tanggal' => now()->addDay()->toDateString(),
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '10:00',
            'toleransi_keterlambatan_menit' => 15,
            'kapasitas_peserta' => 30,
            'aktif' => true,
        ]);

        $page = $this->get('/ujian/jadwal?program_tes_id='.$ujian->program_tes_id.'&ujian_id='.$ujian->id)
            ->assertOk()
            ->inertiaPage();

        $this->assertNotContains($ujian->id, collect($page['props']['ringkasanUjian'])->pluck('id')->all());

        $peserta = Pengguna::where('email', 'peserta@toefl.test')->firstOrFail();

        $this->post('/ujian/slot/'.$slot->id.'/pendaftaran', [
            'nomor_identitas' => $peserta->profilPeserta->nomor_identitas,
            'fakultas_id' => $peserta->profilPeserta->fakultas_id,
            'program_studi_id' => $peserta->profilPeserta->program_studi_id,
            'tahun_mulai' => $peserta->profilPeserta->tahun_mulai,
            'bukti_pembayaran' => UploadedFile::fake()->image('bukti.png'),
        ])->assertRedirect()->assertSessionHas('error', 'Ujian ini tidak tersedia untuk program studi Anda.');

        $this->assertDatabaseMissing('pendaftaran_ujian', ['slot_ujian_id' => $slot->id]);
    }

    public function test_pendaftaran_official_menolak_perubahan_prodi_setelah_peserta_memakai_ujian(): void
    {
        $peserta = $this->masukSebagai('peserta');
        $prodiLain = $this->prodiLain();
        $ujianLama = $this->buatUjian(ModeUjian::TRYOUT->value);

        SesiUjian::create([
            'ujian_id' => $ujianLama->id,
            'pengguna_id' => $peserta->id,
            'status' => StatusSesi::SUBMITTED,
            'dimulai_pada' => now(),
            'diserahkan_pada' => now()->addMinute(),
        ]);

        $ujian = $this->buatUjian(ModeUjian::OFFICIAL->value);
        $periode = PeriodeUjian::create([
            'ujian_id' => $ujian->id,
            'judul' => 'Periode Official Kunci Prodi',
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addDay()->toDateString(),
            'aktif' => true,
        ]);

        PeriodePendaftaranUjian::create([
            'periode_ujian_id' => $periode->id,
            'nama' => 'Pendaftaran Official Kunci Prodi',
            'dibuka_pada' => now()->subDay(),
            'ditutup_pada' => now()->addDay(),
            'aktif' => true,
        ]);

        $slot = SlotUjian::create([
            'periode_ujian_id' => $periode->id,
            'tanggal' => now()->addDay()->toDateString(),
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '10:00',
            'toleransi_keterlambatan_menit' => 15,
            'kapasitas_peserta' => 30,
            'aktif' => true,
        ]);

        $this->post('/ujian/slot/'.$slot->id.'/pendaftaran', [
            'nomor_identitas' => $peserta->profilPeserta->nomor_identitas,
            'fakultas_id' => $prodiLain->fakultas_id,
            'program_studi_id' => $prodiLain->id,
            'tahun_mulai' => $peserta->profilPeserta->tahun_mulai,
            'bukti_pembayaran' => UploadedFile::fake()->image('bukti.png'),
        ])->assertRedirect()->assertSessionHas('error', 'Program studi tidak dapat diubah setelah Anda mengikuti ujian. Hubungi admin.');

        $this->assertDatabaseMissing('pendaftaran_ujian', ['slot_ujian_id' => $slot->id]);
    }

    public function test_admin_tidak_dapat_membuat_periode_untuk_tryout(): void
    {
        $this->masukSebagai('admin');
        $ujian = $this->buatUjian(ModeUjian::TRYOUT->value);

        $this->get('/admin/ujian/'.$ujian->id.'/periode-ujian/tambah')
            ->assertRedirect(route('admin.ujian.detail', $ujian));

        $this->post('/admin/ujian/'.$ujian->id.'/periode-ujian', [
            'judul' => 'Periode Tryout',
            'tanggal_mulai' => now()->addDay()->toDateString(),
            'tanggal_selesai' => now()->addDays(2)->toDateString(),
        ])->assertSessionHas('error', 'Penjadwalan hanya tersedia untuk ujian resmi.');

        $this->assertDatabaseMissing('periode_ujian', ['ujian_id' => $ujian->id]);
    }

    public function test_ujian_tidak_dapat_dihapus_jika_sudah_memiliki_sesi(): void
    {
        $this->masukSebagai('admin');
        $peserta = Pengguna::where('email', 'peserta@toefl.test')->firstOrFail();
        $ujian = $this->buatUjian(ModeUjian::TRYOUT->value);

        SesiUjian::create([
            'ujian_id' => $ujian->id,
            'pengguna_id' => $peserta->id,
            'status' => StatusSesi::SUBMITTED,
            'dimulai_pada' => now(),
            'diserahkan_pada' => now()->addMinute(),
        ]);

        $this->delete('/admin/ujian/'.$ujian->id)
            ->assertSessionHas('error', 'Ujian tidak bisa dihapus karena sudah memiliki sesi.');

        $this->assertDatabaseHas('ujian', ['id' => $ujian->id]);
    }

    public function test_program_studi_yang_dipakai_lingkup_tidak_dapat_dihapus(): void
    {
        $this->masukSebagai('admin');
        $prodi = $this->prodiLain();
        $this->buatUjian(ModeUjian::TRYOUT->value, LingkupUjian::PRODI->value, [$prodi->id]);

        $this->delete('/admin/data-induk/program-studi/'.$prodi->id)
            ->assertSessionHas('error', 'Program studi tidak dapat dihapus karena digunakan pada lingkup ujian.');

        $this->assertDatabaseHas('program_studi', ['id' => $prodi->id]);
    }

    public function test_pembuatan_ujian_lingkup_prodi_mewajibkan_pilihan_prodi(): void
    {
        $this->masukSebagai('admin');
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $bankSoal = BankSoal::where('program_tes_id', $programTes->id)->firstOrFail();

        $this->post('/admin/ujian', [
            'program_tes_id' => $programTes->id,
            'bank_soal_id' => $bankSoal->id,
            'judul' => 'Ujian Prodi Tanpa Pilihan',
            'mode' => ModeUjian::TRYOUT->value,
            'lingkup' => LingkupUjian::PRODI->value,
            'program_studi_ids' => [],
            'durasi_menit' => 120,
        ])->assertSessionHasErrors('program_studi_ids');

        $this->assertDatabaseMissing('ujian', ['judul' => 'Ujian Prodi Tanpa Pilihan']);
    }

    public function test_pembuatan_ujian_lingkup_umum_dengan_prodi_kosong_berhasil(): void
    {
        $this->masukSebagai('admin');
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $bankSoal = BankSoal::where('program_tes_id', $programTes->id)->firstOrFail();

        $this->post('/admin/ujian', [
            'program_tes_id' => $programTes->id,
            'bank_soal_id' => $bankSoal->id,
            'judul' => 'Ujian Umum Tanpa Prodi',
            'mode' => ModeUjian::TRYOUT->value,
            'lingkup' => LingkupUjian::UMUM->value,
            'program_studi_ids' => [],
            'durasi_menit' => 120,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ujian', [
            'judul' => 'Ujian Umum Tanpa Prodi',
            'lingkup' => LingkupUjian::UMUM->value,
        ]);
    }

    public function test_pembaruan_ujian_umum_ke_resmi_dengan_prodi_kosong_berhasil(): void
    {
        $this->masukSebagai('admin');
        $ujian = $this->buatUjian(ModeUjian::TRYOUT->value);

        $this->put('/admin/ujian/'.$ujian->id, [
            'program_tes_id' => $ujian->program_tes_id,
            'judul' => $ujian->judul,
            'mode' => ModeUjian::OFFICIAL->value,
            'lingkup' => LingkupUjian::UMUM->value,
            'program_studi_ids' => [],
            'durasi_menit' => $ujian->durasi_menit,
            'aktif' => true,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ujian', [
            'id' => $ujian->id,
            'mode' => ModeUjian::OFFICIAL->value,
            'lingkup' => LingkupUjian::UMUM->value,
        ]);
    }
}
