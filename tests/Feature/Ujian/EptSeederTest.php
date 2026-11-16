<?php

namespace Tests\Feature\Ujian;

use App\Models\BankSoal;
use App\Models\HasilPenilaian;
use App\Models\PartSoal;
use App\Models\PartUjian;
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

        $this->assertSame(50, Soal::where('section_id', $listening->id)->count());
        $this->assertSame(50, Soal::where('section_id', $reading->id)->count());
    }

    public function test_seeder_membuat_soal_individual_reading_dan_listening_beraudio(): void
    {
        $ept = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $listening = Section::where('program_tes_id', $ept->id)->where('nama', 'Listening')->firstOrFail();
        $reading = Section::where('program_tes_id', $ept->id)->where('nama', 'Reading')->firstOrFail();

        $this->assertSame(
            20,
            Soal::where('section_id', $reading->id)->whereNull('materi_soal_id')->count(),
        );

        $this->assertSame(
            50,
            Soal::where('section_id', $listening->id)
                ->whereHas('materiSoal.mediaMateriSoal', fn ($query) => $query->where('jenis_media', 'audio'))
                ->count(),
        );
    }

    public function test_ujian_ept_memiliki_dua_part_dengan_50_soal(): void
    {
        $ept = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $listening = Section::where('program_tes_id', $ept->id)->where('nama', 'Listening')->firstOrFail();
        $reading = Section::where('program_tes_id', $ept->id)->where('nama', 'Reading')->firstOrFail();
        $ujian = Ujian::where('judul', 'EPT Try Out 1')->firstOrFail();

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

    public function test_skema_penilaian_memakai_raw_maksimum_50_dan_hasil_demo_50_soal(): void
    {
        $ept = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $reading = Section::where('program_tes_id', $ept->id)->where('nama', 'Reading')->firstOrFail();
        $skema = SkemaPenilaian::where('program_tes_id', $ept->id)->where('status', 'aktif')->firstOrFail();

        $this->assertSame(50, $skema->aturanPenilaian()->where('section_id', $reading->id)->value('skor_mentah_maksimum'));
        $this->assertSame('400.0', $skema->skala_total_maksimum);
        $this->assertSame(2, HasilPenilaian::count());
        $this->assertSame(50, HasilPenilaian::where('section_id', $reading->id)->value('jumlah_soal'));
    }

    public function test_bank_soal_ept_dan_bank_latihan_tersedia(): void
    {
        $this->assertDatabaseHas('bank_soal', ['nama' => 'Bank Soal EPT']);
        $this->assertDatabaseHas('bank_soal', ['nama' => 'Bank Soal Latihan']);

        $bankEpt = BankSoal::where('nama', 'Bank Soal EPT')->firstOrFail();
        $this->assertSame(5, PartSoal::where('bank_soal_id', $bankEpt->id)->count());
    }
}
