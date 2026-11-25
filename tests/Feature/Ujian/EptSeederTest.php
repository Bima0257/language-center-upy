<?php

namespace Tests\Feature\Ujian;

use App\Enums\StatusPendaftaranUjian;
use App\Models\BankSoal;
use App\Models\HasilPenilaian;
use App\Models\PartSoal;
use App\Models\PartUjian;
use App\Models\PendaftaranUjian;
use App\Models\Pengguna;
use App\Models\ProgramTes;
use App\Models\Section;
use App\Models\SkemaPenilaian;
use App\Models\Soal;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EptSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_seeder_membuat_50_soal_listening_dan_50_soal_reading(): void
    {
        $ept = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $listening = Section::where('program_tes_id', $ept->id)->where('nama', 'Listening')->firstOrFail();
        $reading = Section::where('program_tes_id', $ept->id)->where('nama', 'Reading')->firstOrFail();

        foreach (['Bank Soal Ujian Resmi', 'Bank Soal Ujian Tryout'] as $namaBank) {
            $bank = BankSoal::where('nama', $namaBank)->firstOrFail();

            $this->assertSame(
                50,
                Soal::where('bank_soal_id', $bank->id)->where('section_id', $listening->id)->count(),
            );
            $this->assertSame(
                50,
                Soal::where('bank_soal_id', $bank->id)->where('section_id', $reading->id)->count(),
            );
        }
    }

    public function test_seeder_membuat_soal_individual_reading_dan_listening_beraudio(): void
    {
        $ept = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $listening = Section::where('program_tes_id', $ept->id)->where('nama', 'Listening')->firstOrFail();
        $reading = Section::where('program_tes_id', $ept->id)->where('nama', 'Reading')->firstOrFail();

        foreach (['Bank Soal Ujian Resmi', 'Bank Soal Ujian Tryout'] as $namaBank) {
            $bank = BankSoal::where('nama', $namaBank)->firstOrFail();

            $this->assertSame(
                20,
                Soal::where('bank_soal_id', $bank->id)
                    ->where('section_id', $reading->id)
                    ->whereNull('materi_soal_id')
                    ->count(),
            );

            $this->assertSame(
                50,
                Soal::where('bank_soal_id', $bank->id)
                    ->where('section_id', $listening->id)
                    ->whereHas('materiSoal.mediaMateriSoal', fn ($query) => $query->where('jenis_media', 'audio'))
                    ->count(),
            );
        }
    }

    public function test_ujian_ept_memiliki_dua_part_dengan_50_soal(): void
    {
        $ept = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $listening = Section::where('program_tes_id', $ept->id)->where('nama', 'Listening')->firstOrFail();
        $reading = Section::where('program_tes_id', $ept->id)->where('nama', 'Reading')->firstOrFail();

        $this->assertSame(45, $listening->durasi_menit);
        $this->assertSame(75, $reading->durasi_menit);

        foreach (['Ujian Resmi EPT', 'Ujian Tryout EPT'] as $judul) {
            $ujian = Ujian::where('judul', $judul)->firstOrFail();

            $this->assertSame(120, $ujian->durasi_menit);
            $this->assertSame(2, $ujian->partUjian()->count());
            $this->assertSame(
                50,
                PartUjian::where('ujian_id', $ujian->id)->where('section_id', $listening->id)->value('jumlah_soal'),
            );
            $this->assertSame(
                50,
                PartUjian::where('ujian_id', $ujian->id)->where('section_id', $reading->id)->value('jumlah_soal'),
            );
        }
    }

    public function test_ujian_resmi_memiliki_jadwal_dan_ujian_tryout_tanpa_jadwal(): void
    {
        $ujianResmi = Ujian::where('judul', 'Ujian Resmi EPT')->firstOrFail();
        $ujianTryout = Ujian::where('judul', 'Ujian Tryout EPT')->firstOrFail();

        $this->assertSame('official', $ujianResmi->mode->value);
        $this->assertSame('tryout', $ujianTryout->mode->value);
        $this->assertGreaterThan(0, $ujianResmi->periodeUjian()->count());
        $this->assertSame(0, $ujianTryout->periodeUjian()->count());
    }

    public function test_seed_menyediakan_alur_pendaftaran_dan_pengerjaan_ujian_resmi(): void
    {
        $ujianResmi = Ujian::where('judul', 'Ujian Resmi EPT')->firstOrFail();
        $peserta = Pengguna::where('email', 'peserta@toefl.test')->firstOrFail();

        $this->assertTrue(
            PendaftaranUjian::where('pengguna_id', $peserta->id)
                ->where('status', StatusPendaftaranUjian::APPROVED)
                ->whereHas('slotUjian.periodeUjian', fn ($query) => $query->where('ujian_id', $ujianResmi->id))
                ->exists(),
        );

        $periodePendaftaran = $ujianResmi->periodeUjian()
            ->where('judul', 'Gelombang Pendaftaran Peserta')
            ->firstOrFail();

        $this->assertNotNull($periodePendaftaran->periodePendaftaran);
        $this->assertTrue($periodePendaftaran->periodePendaftaran->sedangDibuka());
        $this->assertTrue(
            $periodePendaftaran->slotUjian()
                ->whereDate('tanggal', '>', now()->toDateString())
                ->exists(),
        );
    }

    public function test_skema_penilaian_memakai_raw_maksimum_50_dan_hasil_demo_50_soal(): void
    {
        $ept = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $reading = Section::where('program_tes_id', $ept->id)->where('nama', 'Reading')->firstOrFail();
        $skema = SkemaPenilaian::where('program_tes_id', $ept->id)->firstOrFail();

        $this->assertSame(50, $skema->aturanPenilaian()->where('section_id', $reading->id)->value('skor_mentah_maksimum'));
        $this->assertSame('400.0', $skema->skala_total_maksimum);
        $this->assertSame(2, HasilPenilaian::count());
        $this->assertSame(50, HasilPenilaian::where('section_id', $reading->id)->value('jumlah_soal'));
    }

    public function test_bank_soal_ujian_dan_bank_latihan_tersedia(): void
    {
        $this->assertDatabaseHas('bank_soal', ['nama' => 'Bank Soal Ujian Resmi']);
        $this->assertDatabaseHas('bank_soal', ['nama' => 'Bank Soal Ujian Tryout']);
        $this->assertDatabaseHas('bank_soal', ['nama' => 'Bank Soal Latihan']);

        foreach (['Bank Soal Ujian Resmi', 'Bank Soal Ujian Tryout', 'Bank Soal Latihan'] as $namaBank) {
            $bank = BankSoal::where('nama', $namaBank)->firstOrFail();

            $this->assertSame(5, PartSoal::where('bank_soal_id', $bank->id)->count());
        }
    }
}
