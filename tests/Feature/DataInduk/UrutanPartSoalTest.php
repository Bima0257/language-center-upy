<?php

namespace Tests\Feature\DataInduk;

use App\Models\BankSoal;
use App\Models\PartSoal;
use App\Models\PartSoalUjian;
use App\Models\PartUjian;
use App\Models\Pengguna;
use App\Models\Section;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UrutanPartSoalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function masukAdmin(): void
    {
        $pengguna = Pengguna::where('email', 'admin@toefl.test')->firstOrFail();

        $this->post('/login', [
            'email' => $pengguna->email,
            'password' => 'password',
        ])->assertRedirect();
    }

    private function buatUjianDenganPart(BankSoal $bankSoal, Section $section, bool $aktif): PartUjian
    {
        $ujian = Ujian::create([
            'program_tes_id' => $bankSoal->program_tes_id,
            'judul' => 'Ujian Urutan '.($aktif ? 'Aktif' : 'Nonaktif').' '.uniqid(),
            'mode' => 'tryout',
            'durasi_menit' => 60,
            'aktif' => $aktif,
        ]);

        return PartUjian::create([
            'ujian_id' => $ujian->id,
            'bank_soal_id' => $bankSoal->id,
            'section_id' => $section->id,
            'judul' => $section->nama,
            'urutan' => 1,
            'jumlah_soal' => 0,
        ]);
    }

    public function test_simpan_part_menempatkan_urutan_otomatis_di_akhir(): void
    {
        $this->masukAdmin();

        $bankSoal = BankSoal::where('nama', 'Bank Soal Latihan')->firstOrFail();
        $section = Section::where('nama', 'Reading')->firstOrFail();
        $urutanTerakhir = PartSoal::where('bank_soal_id', $bankSoal->id)
            ->where('section_id', $section->id)
            ->max('urutan');

        $this->post('/admin/data-induk/part-soal', [
            'bank_soal_id' => $bankSoal->id,
            'section_id' => $section->id,
            'nama' => 'Part Auto Order',
            'petunjuk' => 'Instruksi test.',
            'aktif' => true,
        ])->assertRedirect();

        $partSoal = PartSoal::where('bank_soal_id', $bankSoal->id)
            ->where('section_id', $section->id)
            ->where('nama', 'Part Auto Order')
            ->firstOrFail();

        $this->assertSame((int) $urutanTerakhir + 1, (int) $partSoal->urutan);
    }

    public function test_pengurutan_part_mengubah_urutan_sesuai_urutan_baru(): void
    {
        $this->masukAdmin();

        $bankSoal = BankSoal::where('nama', 'Bank Soal Latihan')->firstOrFail();
        $section = Section::where('nama', 'Reading')->firstOrFail();
        $daftarPartSoal = PartSoal::where('bank_soal_id', $bankSoal->id)
            ->where('section_id', $section->id)
            ->orderBy('urutan')
            ->get();

        $this->assertGreaterThan(1, $daftarPartSoal->count());

        $reversed = $daftarPartSoal->pluck('id')->reverse()->values()->all();

        $this->post('/admin/data-induk/part-soal/urutkan', [
            'part_soal' => $reversed,
        ])->assertRedirect();

        $updated = PartSoal::whereIn('id', $reversed)
            ->orderBy('urutan')
            ->pluck('id')
            ->all();

        $this->assertSame($reversed, $updated);
    }

    public function test_pengurutan_ditolak_jika_ada_part_dipakai_ujian_aktif(): void
    {
        $this->masukAdmin();

        $bankSoal = BankSoal::where('nama', 'Bank Soal Latihan')->firstOrFail();
        $section = Section::where('nama', 'Reading')->firstOrFail();
        $daftarPartSoal = PartSoal::where('bank_soal_id', $bankSoal->id)
            ->where('section_id', $section->id)
            ->orderBy('urutan')
            ->get();
        $idsAwal = $daftarPartSoal->pluck('id')->all();
        $reversed = array_reverse($idsAwal);

        $partUjianNonaktif = $this->buatUjianDenganPart($bankSoal, $section, false);
        $partUjianAktif = $this->buatUjianDenganPart($bankSoal, $section, true);
        foreach ($idsAwal as $index => $partSoalId) {
            PartSoalUjian::create([
                'part_ujian_id' => $partUjianNonaktif->id,
                'part_soal_id' => $partSoalId,
                'urutan' => $index + 1,
            ]);
            PartSoalUjian::create([
                'part_ujian_id' => $partUjianAktif->id,
                'part_soal_id' => $partSoalId,
                'urutan' => $index + 1,
            ]);
        }

        $this->post('/admin/data-induk/part-soal/urutkan', [
            'part_soal' => $reversed,
        ])->assertSessionHasErrors('part_soal');

        $urutanNonaktif = PartSoalUjian::where('part_ujian_id', $partUjianNonaktif->id)
            ->orderBy('urutan')
            ->pluck('part_soal_id')
            ->all();
        $urutanAktif = PartSoalUjian::where('part_ujian_id', $partUjianAktif->id)
            ->orderBy('urutan')
            ->pluck('part_soal_id')
            ->all();

        $this->assertSame($idsAwal, $urutanNonaktif);
        $this->assertSame($idsAwal, $urutanAktif);
    }

    public function test_pengurutan_menyinkronkan_ujian_nonaktif(): void
    {
        $this->masukAdmin();

        $bankSoal = BankSoal::where('nama', 'Bank Soal Latihan')->firstOrFail();
        $section = Section::where('nama', 'Reading')->firstOrFail();
        $daftarPartSoal = PartSoal::where('bank_soal_id', $bankSoal->id)
            ->where('section_id', $section->id)
            ->orderBy('urutan')
            ->get();
        $idsAwal = $daftarPartSoal->pluck('id')->all();
        $reversed = array_reverse($idsAwal);

        $partUjianNonaktif = $this->buatUjianDenganPart($bankSoal, $section, false);
        foreach ($idsAwal as $index => $partSoalId) {
            PartSoalUjian::create([
                'part_ujian_id' => $partUjianNonaktif->id,
                'part_soal_id' => $partSoalId,
                'urutan' => $index + 1,
            ]);
        }

        $this->post('/admin/data-induk/part-soal/urutkan', [
            'part_soal' => $reversed,
        ])->assertRedirect();

        $urutanNonaktif = PartSoalUjian::where('part_ujian_id', $partUjianNonaktif->id)
            ->orderBy('urutan')
            ->pluck('part_soal_id')
            ->all();

        $this->assertSame($reversed, $urutanNonaktif);
    }
}
