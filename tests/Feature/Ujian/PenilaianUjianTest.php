<?php

namespace Tests\Feature\Ujian;

use App\Enums\ModeUjian;
use App\Enums\StatusSesi;
use App\Models\AturanPenilaian;
use App\Models\BankSoal;
use App\Models\InterpretasiSkor;
use App\Models\Jawaban;
use App\Models\PartUjian;
use App\Models\Pengguna;
use App\Models\PeriodeUjian;
use App\Models\ProgramTes;
use App\Models\Section;
use App\Models\SesiUjian;
use App\Models\SkemaPenilaian;
use App\Models\SlotUjian;
use App\Models\Soal;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenilaianUjianTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    /**
     * @return array{pengguna: Pengguna, sesi: SesiUjian, soal: array<int, Soal>}
     */
    private function buatSesi(bool $buatAturan = true): array
    {
        $pengguna = Pengguna::where('email', 'peserta@toefl.test')->firstOrFail();
        $programTes = ProgramTes::create([
            'kode' => '999',
            'nama' => 'Tes Penilaian Dinamis',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);
        $section = Section::create([
            'program_tes_id' => $programTes->id,
            'kode' => '999001',
            'nama' => 'Reading',
            'urutan' => 1,
            'aktif' => true,
        ]);
        $bankSoal = BankSoal::create([
            'program_tes_id' => $programTes->id,
            'nama' => 'Bank Tes Penilaian Dinamis',
            'aktif' => true,
        ]);
        $ujian = Ujian::create([
            'program_tes_id' => $programTes->id,
            'judul' => 'Ujian Penilaian Dinamis',
            'mode' => ModeUjian::OFFICIAL->value,
            'durasi_menit' => 60,
            'aktif' => true,
        ]);
        $partUjian = PartUjian::create([
            'ujian_id' => $ujian->id,
            'bank_soal_id' => $bankSoal->id,
            'section_id' => $section->id,
            'judul' => 'Reading',
            'urutan' => 1,
            'jumlah_soal' => 2,
        ]);
        $soal = collect(range(1, 2))->map(function (int $urutan) use ($bankSoal, $section): Soal {
            return Soal::create([
                'bank_soal_id' => $bankSoal->id,
                'section_id' => $section->id,
                'teks_soal' => "Pertanyaan {$urutan}",
                'pilihan_a' => 'Pilihan A',
                'pilihan_b' => 'Pilihan B',
                'pilihan_c' => 'Pilihan C',
                'pilihan_d' => 'Pilihan D',
                'kunci_jawaban' => 'A',
                'urutan' => $urutan,
            ]);
        })->values()->all();

        foreach ($soal as $dataSoal) {
            $partUjian->soalPartUjian()->create([
                'soal_id' => $dataSoal->id,
                'urutan' => $dataSoal->urutan,
            ]);
        }

        if ($buatAturan) {
            $skema = SkemaPenilaian::create([
                'program_tes_id' => $programTes->id,
                'metode_total' => 'jumlah',
                'skala_total_minimum' => 10,
                'skala_total_maksimum' => 100,
            ]);

            AturanPenilaian::create([
                'skema_penilaian_id' => $skema->id,
                'program_tes_id' => $programTes->id,
                'section_id' => $section->id,
                'kode_section' => $section->kode,
                'tabel_konversi' => [
                    0 => 10,
                    1 => 55,
                    2 => 100,
                ],
                'skor_mentah_maksimum' => 2,
                'skor_skala_minimum' => 10,
                'skor_skala_maksimum' => 100,
            ]);

            InterpretasiSkor::create([
                'skema_penilaian_id' => $skema->id,
                'program_tes_id' => $programTes->id,
                'skor_minimum' => 0,
                'skor_maksimum' => 54,
                'tingkat_cefr' => 'A1',
                'label_tingkat' => 'Beginning',
                'lulus' => false,
                'deskripsi' => 'Beginner',
            ]);

            InterpretasiSkor::create([
                'skema_penilaian_id' => $skema->id,
                'program_tes_id' => $programTes->id,
                'skor_minimum' => 55,
                'skor_maksimum' => 100,
                'tingkat_cefr' => 'A2',
                'label_tingkat' => 'Basic',
                'lulus' => true,
                'deskripsi' => 'Elementary',
            ]);
        }

        $periodeUjian = PeriodeUjian::create([
            'ujian_id' => $ujian->id,
            'judul' => 'Periode Penilaian Dinamis',
            'tanggal_mulai' => now()->subDay(),
            'tanggal_selesai' => now()->addDay(),
            'aktif' => true,
        ]);
        $slotUjian = SlotUjian::create([
            'periode_ujian_id' => $periodeUjian->id,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => now()->subMinutes(5)->format('H:i:s'),
            'waktu_selesai' => now()->addHour()->format('H:i:s'),
            'toleransi_keterlambatan_menit' => 15,
            'kapasitas_peserta' => 30,
            'aktif' => true,
        ]);
        $sesi = SesiUjian::create([
            'slot_ujian_id' => $slotUjian->id,
            'pengguna_id' => $pengguna->id,
            'status' => StatusSesi::IN_PROGRESS,
            'dimulai_pada' => now()->subMinutes(10),
            'keaktifan_terakhir_pada' => now(),
            'jumlah_pelanggaran' => 0,
            'ditandai' => false,
        ]);

        return compact('pengguna', 'sesi', 'soal');
    }

    public function test_submit_menggunakan_semua_soal_ujian_dan_tabel_konversi(): void
    {
        $dataUjian = $this->buatSesi();
        Jawaban::create([
            'sesi_ujian_id' => $dataUjian['sesi']->id,
            'soal_id' => $dataUjian['soal'][0]->id,
            'teks_jawaban' => 'A',
        ]);

        $response = $this->actingAs($dataUjian['pengguna'])
            ->post("/ujian/sesi/{$dataUjian['sesi']->id}/serahkan");

        $response->assertRedirect(route('dasbor'));

        $sesi = $dataUjian['sesi']->fresh();
        $this->assertSame(StatusSesi::SUBMITTED, $sesi->status);
        $this->assertSame('55.0', $sesi->skor_membaca);
        $this->assertSame('0.0', $sesi->skor_menyimak);
        $this->assertSame('55.0', $sesi->skor_total);
        $this->assertSame('100.0', $sesi->skor_total_maksimum);
        $this->assertTrue($sesi->lulus);
        $this->assertSame('A2', $sesi->tingkat_cefr);
        $this->assertSame('Basic', $sesi->label_tingkat);
        $this->assertNotNull($sesi->snapshot_penilaian);
        $this->assertDatabaseHas('jawaban', [
            'id' => Jawaban::where('sesi_ujian_id', $sesi->id)->firstOrFail()->id,
            'benar' => true,
        ]);
        $this->assertDatabaseHas('hasil_penilaian', [
            'sesi_ujian_id' => $sesi->id,
            'section_id' => $dataUjian['soal'][0]->section_id,
            'jumlah_benar' => 1,
            'jumlah_soal' => 2,
            'skor_skala' => 55,
        ]);
    }

    public function test_submit_menolak_jika_aturan_section_belum_dikonfigurasi(): void
    {
        $dataUjian = $this->buatSesi(false);

        $this->actingAs($dataUjian['pengguna'])
            ->post("/ujian/sesi/{$dataUjian['sesi']->id}/serahkan")
            ->assertSessionHas('error', 'Snapshot aturan penilaian tidak memiliki konfigurasi section.');

        $this->assertSame(StatusSesi::IN_PROGRESS, $dataUjian['sesi']->fresh()->status);
    }

    public function test_jawaban_dengan_pilihan_tidak_valid_ditolak(): void
    {
        $dataUjian = $this->buatSesi();

        $this->actingAs($dataUjian['pengguna'])
            ->postJson("/ujian/sesi/{$dataUjian['sesi']->id}/jawaban", [
                'soal_id' => $dataUjian['soal'][0]->id,
                'teks_jawaban' => 'Z',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('teks_jawaban');
    }

    public function test_jawaban_dari_soal_di_luar_ujian_ditolak(): void
    {
        $dataUjian = $this->buatSesi();
        $soalDiLuarUjian = Soal::create([
            'bank_soal_id' => $dataUjian['soal'][0]->bank_soal_id,
            'section_id' => $dataUjian['soal'][0]->section_id,
            'teks_soal' => 'Soal di luar ujian',
            'pilihan_a' => 'Pilihan A',
            'pilihan_b' => 'Pilihan B',
            'pilihan_c' => 'Pilihan C',
            'pilihan_d' => 'Pilihan D',
            'kunci_jawaban' => 'A',
            'urutan' => 3,
        ]);

        $this->actingAs($dataUjian['pengguna'])
            ->postJson("/ujian/sesi/{$dataUjian['sesi']->id}/jawaban", [
                'soal_id' => $soalDiLuarUjian->id,
                'teks_jawaban' => 'A',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('soal_id');
    }
}
