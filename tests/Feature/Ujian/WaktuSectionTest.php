<?php

namespace Tests\Feature\Ujian;

use App\Enums\StatusSesi;
use App\Models\BankSoal;
use App\Models\PartUjian;
use App\Models\Pengguna;
use App\Models\ProgramTes;
use App\Models\Section;
use App\Models\SesiPartUjian;
use App\Models\SesiUjian;
use App\Models\Soal;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WaktuSectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        Carbon::setTestNow(Carbon::parse('2026-11-24 08:00:00'));

        SesiUjian::where('pengguna_id', Pengguna::where('email', 'peserta@toefl.test')->value('id'))
            ->where('status', StatusSesi::IN_PROGRESS->value)
            ->delete();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_mulai_sesi_membuat_timing_section_pertama(): void
    {
        [$sesi, $data] = $this->mulaiSesi();

        $timing = SesiPartUjian::where('sesi_ujian_id', $sesi->id)->firstOrFail();

        $this->assertSame($data['part1']->id, $timing->part_ujian_id);
        $this->assertSame(45, $timing->durasi_menit);
        $this->assertNull($timing->selesai_pada);
        $this->assertTrue($sesi->dimulai_pada->equalTo($timing->dimulai_pada));
    }

    public function test_halaman_kerja_mengirim_sisa_waktu_section_dan_total(): void
    {
        [$sesi, $data] = $this->mulaiSesi();

        $page = $this->get('/ujian/sesi/'.$sesi->id)
            ->assertOk()
            ->inertiaPage();

        $waktu = $page['props']['waktu'];

        $this->assertSame($data['part1']->id, $waktu['section']['part_ujian_id']);
        $this->assertSame(45 * 60, $waktu['section']['sisa_detik']);
        $this->assertSame(120 * 60, $waktu['total']['sisa_detik']);
        $this->assertSame(2, $waktu['section']['jumlah']);
        $this->assertFalse($waktu['section']['terakhir']);
    }

    public function test_selesaikan_part_sebelum_waktu_habis_tidak_memindahkan_section(): void
    {
        [$sesi, $data] = $this->mulaiSesi();

        $response = $this->postJson('/ujian/sesi/'.$sesi->id.'/selesaikan-part')
            ->assertOk();

        $response->assertJsonPath('status', 'lanjut');
        $response->assertJsonPath('part_ujian_aktif_id', $data['part1']->id);
        $this->assertSame(1, SesiPartUjian::where('sesi_ujian_id', $sesi->id)->count());
    }

    public function test_section_timeout_berpindah_ke_section_berikutnya(): void
    {
        [$sesi, $data] = $this->mulaiSesi();

        Carbon::setTestNow(now()->addMinutes(46));

        $response = $this->postJson('/ujian/sesi/'.$sesi->id.'/keaktifan')
            ->assertOk();

        $response->assertJsonPath('status', 'pindah_section');
        $response->assertJsonPath('part_ujian_aktif_id', $data['part2']->id);
        $response->assertJsonPath('sesi_aktif', true);

        $timingPertama = SesiPartUjian::where('sesi_ujian_id', $sesi->id)
            ->where('part_ujian_id', $data['part1']->id)
            ->firstOrFail();
        $timingKedua = SesiPartUjian::where('sesi_ujian_id', $sesi->id)
            ->where('part_ujian_id', $data['part2']->id)
            ->firstOrFail();

        $this->assertNotNull($timingPertama->selesai_pada);
        $this->assertSame('waktu_habis', $timingPertama->alasan_selesai?->value);
        $this->assertSame($data['part2']->id, $timingKedua->part_ujian_id);
        $this->assertSame(
            $sesi->dimulai_pada->copy()->addMinutes(45)->timestamp,
            $timingKedua->dimulai_pada->timestamp,
        );
        $this->assertGreaterThan(0, $response->json('waktu.section.sisa_detik'));
    }

    public function test_selesaikan_section_ditolak_jika_belum_semua_terjawab(): void
    {
        [$sesi, $data] = $this->mulaiSesi();

        $this->postJson('/ujian/sesi/'.$sesi->id.'/selesaikan-section', [
            'part_ujian_id' => $data['part1']->id,
        ])->assertStatus(422)->assertJsonValidationErrors('section');

        $this->assertSame($data['part1']->id, $sesi->fresh()->part_ujian_aktif_id);
    }

    public function test_selesaikan_section_manual_memindahkan_dan_mencatat_alasan(): void
    {
        [$sesi, $data] = $this->mulaiSesi();

        $this->postJson('/ujian/sesi/'.$sesi->id.'/jawaban', [
            'soal_id' => $data['soal1']->id,
            'teks_jawaban' => 'A',
        ])->assertOk();

        $response = $this->postJson('/ujian/sesi/'.$sesi->id.'/selesaikan-section', [
            'part_ujian_id' => $data['part1']->id,
        ])->assertOk();

        $response->assertJsonPath('status', 'pindah_section');
        $response->assertJsonPath('part_ujian_aktif_id', $data['part2']->id);

        $timingPertama = SesiPartUjian::where('sesi_ujian_id', $sesi->id)
            ->where('part_ujian_id', $data['part1']->id)
            ->firstOrFail();

        $this->assertSame('manual', $timingPertama->alasan_selesai?->value);
        $this->assertSame($data['part2']->id, $sesi->fresh()->part_ujian_aktif_id);

        $this->postJson('/ujian/sesi/'.$sesi->id.'/jawaban', [
            'soal_id' => $data['soal2']->id,
            'teks_jawaban' => 'B',
        ])->assertOk();

        $response = $this->postJson('/ujian/sesi/'.$sesi->id.'/selesaikan-section', [
            'part_ujian_id' => $data['part2']->id,
        ])->assertOk();

        $response->assertJsonPath('diserahkan', true);
        $this->assertSame(StatusSesi::SUBMITTED, $sesi->fresh()->status);
    }

    public function test_total_timeout_auto_submit_dan_menilai(): void
    {
        [$sesi] = $this->mulaiSesi();

        Carbon::setTestNow(now()->addMinutes(121));

        $response = $this->postJson('/ujian/sesi/'.$sesi->id.'/keaktifan')
            ->assertOk();

        $response->assertJsonPath('status', 'total_habis');
        $response->assertJsonPath('diserahkan', true);
        $response->assertJsonPath('sesi_aktif', false);

        $sesi->refresh();

        $this->assertSame(StatusSesi::SUBMITTED, $sesi->status);
        $this->assertNotNull($sesi->diserahkan_pada);
        $this->assertNotNull($sesi->skor_total);
        $this->assertSame(
            0,
            SesiPartUjian::where('sesi_ujian_id', $sesi->id)->whereNull('selesai_pada')->count(),
        );
    }

    public function test_jawaban_dari_section_lain_ditolak(): void
    {
        [$sesi, $data] = $this->mulaiSesi();

        $this->postJson('/ujian/sesi/'.$sesi->id.'/jawaban', [
            'soal_id' => $data['soal2']->id,
            'teks_jawaban' => 'A',
        ])->assertStatus(422)->assertJsonValidationErrors('soal_id');
    }

    public function test_durasi_section_melebihi_total_ditolak_saat_mulai(): void
    {
        $this->masukSebagaiPeserta();

        $data = $this->buatUjianUji(60);

        $this->post('/ujian/tryout/'.$data['ujian']->id.'/mulai')
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('sesi_ujian', [
            'ujian_id' => $data['ujian']->id,
        ]);
    }

    public function test_admin_tidak_dapat_menyimpan_ujian_dengan_total_lebih_kecil_dari_section(): void
    {
        $admin = Pengguna::where('email', 'admin@toefl.test')->firstOrFail();

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $bankSoal = BankSoal::where('program_tes_id', $programTes->id)->firstOrFail();

        $this->post('/admin/ujian', [
            'program_tes_id' => $programTes->id,
            'bank_soal_id' => $bankSoal->id,
            'judul' => 'Ujian Total Terlalu Kecil',
            'mode' => 'tryout',
            'lingkup' => 'umum',
            'durasi_menit' => 60,
        ])->assertSessionHasErrors('durasi_menit');

        $this->assertDatabaseMissing('ujian', [
            'judul' => 'Ujian Total Terlalu Kecil',
        ]);
    }

    private function masukSebagaiPeserta(): Pengguna
    {
        $pengguna = Pengguna::where('email', 'peserta@toefl.test')->firstOrFail();

        $this->post('/login', [
            'email' => $pengguna->email,
            'password' => 'password',
        ]);

        return $pengguna;
    }

    /**
     * @return array{ujian: Ujian, part1: PartUjian, part2: PartUjian, soal1: Soal, soal2: Soal}
     */
    private function buatUjianUji(int $durasiMenit = 120): array
    {
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $bankSoal = BankSoal::where('nama', 'Bank Soal Ujian Resmi')->firstOrFail();
        $listening = Section::where('program_tes_id', $programTes->id)->where('nama', 'Listening')->firstOrFail();
        $reading = Section::where('program_tes_id', $programTes->id)->where('nama', 'Reading')->firstOrFail();

        $ujian = Ujian::create([
            'program_tes_id' => $programTes->id,
            'judul' => 'Ujian Waktu Section '.uniqid(),
            'mode' => 'tryout',
            'lingkup' => 'umum',
            'durasi_menit' => $durasiMenit,
            'aktif' => true,
        ]);

        $part1 = PartUjian::create([
            'ujian_id' => $ujian->id,
            'bank_soal_id' => $bankSoal->id,
            'section_id' => $listening->id,
            'judul' => 'Listening Section',
            'urutan' => 1,
            'jumlah_soal' => 1,
        ]);

        $part2 = PartUjian::create([
            'ujian_id' => $ujian->id,
            'bank_soal_id' => $bankSoal->id,
            'section_id' => $reading->id,
            'judul' => 'Reading Section',
            'urutan' => 2,
            'jumlah_soal' => 1,
        ]);

        $soal1 = $this->buatSoal($bankSoal, $listening, 'Soal Listening Waktu', 1);
        $soal2 = $this->buatSoal($bankSoal, $reading, 'Soal Reading Waktu', 1);

        $part1->soalPartUjian()->create(['soal_id' => $soal1->id, 'urutan' => 1]);
        $part2->soalPartUjian()->create(['soal_id' => $soal2->id, 'urutan' => 1]);

        return [
            'ujian' => $ujian,
            'part1' => $part1,
            'part2' => $part2,
            'soal1' => $soal1,
            'soal2' => $soal2,
        ];
    }

    /**
     * @return array{0: SesiUjian, 1: array{ujian: Ujian, part1: PartUjian, part2: PartUjian, soal1: Soal, soal2: Soal}}
     */
    private function mulaiSesi(): array
    {
        $pengguna = $this->masukSebagaiPeserta();
        $data = $this->buatUjianUji();

        $this->post('/ujian/tryout/'.$data['ujian']->id.'/mulai')->assertRedirect();

        $sesi = SesiUjian::where('ujian_id', $data['ujian']->id)
            ->where('pengguna_id', $pengguna->id)
            ->latest('created_at')
            ->firstOrFail();

        return [$sesi, $data];
    }

    private function buatSoal(BankSoal $bankSoal, Section $section, string $teks, int $urutan): Soal
    {
        return Soal::create([
            'bank_soal_id' => $bankSoal->id,
            'section_id' => $section->id,
            'teks_soal' => $teks,
            'pilihan_a' => 'Pilihan A',
            'pilihan_b' => 'Pilihan B',
            'pilihan_c' => 'Pilihan C',
            'pilihan_d' => 'Pilihan D',
            'kunci_jawaban' => 'A',
            'urutan' => $urutan,
        ]);
    }
}
