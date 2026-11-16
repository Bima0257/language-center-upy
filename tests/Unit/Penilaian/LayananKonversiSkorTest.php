<?php

namespace Tests\Unit\Penilaian;

use App\Modules\Penilaian\Services\LayananInterpretasiSkor;
use App\Modules\Penilaian\Services\LayananKonversiSkor;
use PHPUnit\Framework\TestCase;

class LayananKonversiSkorTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function aturan(): array
    {
        $tabel = [];

        for ($skorMentah = 0; $skorMentah <= 50; $skorMentah++) {
            $tabel[$skorMentah] = 100 + ($skorMentah * 2);
        }

        return [
            'section_id' => 'section-1',
            'kode_section' => '001001',
            'nama_section' => 'Listening',
            'tabel_konversi' => $tabel,
            'skor_mentah_maksimum' => 50,
            'skor_skala_minimum' => 100,
            'skor_skala_maksimum' => 200,
        ];
    }

    public function test_konversi_absolut_mengikuti_tabel(): void
    {
        $layanan = new LayananKonversiSkor;
        $aturan = $this->aturan();

        $this->assertSame(100.0, $layanan->konversi($aturan, 0));
        $this->assertSame(108.0, $layanan->konversi($aturan, 4));
        $this->assertSame(174.0, $layanan->konversi($aturan, 37));
        $this->assertSame(186.0, $layanan->konversi($aturan, 43));
        $this->assertSame(200.0, $layanan->konversi($aturan, 50));
    }

    public function test_konversi_menolak_skor_mentah_di_luar_rentang(): void
    {
        $layanan = new LayananKonversiSkor;

        $this->expectException(\RuntimeException::class);

        $layanan->konversi($this->aturan(), 51);
    }

    public function test_konversi_menolak_tabel_tidak_lengkap(): void
    {
        $layanan = new LayananKonversiSkor;
        $aturan = $this->aturan();
        unset($aturan['tabel_konversi'][10]);

        $this->expectException(\RuntimeException::class);

        $layanan->konversi($aturan, 10);
    }

    public function test_interpretasi_total_360_adalah_b2_minus_dan_lulus(): void
    {
        $layanan = new LayananInterpretasiSkor;

        $hasil = $layanan->cari($this->interpretasi(), 360);

        $this->assertSame('B2-', $hasil['tingkat_cefr']);
        $this->assertSame('Very Good', $hasil['label_tingkat']);
        $this->assertSame('Independent User', $hasil['deskripsi']);
        $this->assertTrue($hasil['lulus']);
    }

    public function test_batas_lulus_dinamis_mengikuti_interpretasi(): void
    {
        $layanan = new LayananInterpretasiSkor;

        $this->assertFalse($layanan->cari($this->interpretasi(), 299)['lulus']);
        $this->assertTrue($layanan->cari($this->interpretasi(), 300)['lulus']);
        $this->assertSame('A1', $layanan->cari($this->interpretasi(), 200)['tingkat_cefr']);
        $this->assertSame('B2', $layanan->cari($this->interpretasi(), 400)['tingkat_cefr']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function interpretasi(): array
    {
        return [
            ['skor_minimum' => 380, 'skor_maksimum' => 400, 'tingkat_cefr' => 'B2', 'label_tingkat' => 'Excellent', 'lulus' => true, 'deskripsi' => 'Very Good User'],
            ['skor_minimum' => 360, 'skor_maksimum' => 379, 'tingkat_cefr' => 'B2-', 'label_tingkat' => 'Very Good', 'lulus' => true, 'deskripsi' => 'Independent User'],
            ['skor_minimum' => 340, 'skor_maksimum' => 359, 'tingkat_cefr' => 'B1+', 'label_tingkat' => 'Good', 'lulus' => true, 'deskripsi' => 'Upper Intermediate'],
            ['skor_minimum' => 320, 'skor_maksimum' => 339, 'tingkat_cefr' => 'B1', 'label_tingkat' => 'Competent', 'lulus' => true, 'deskripsi' => 'Intermediate'],
            ['skor_minimum' => 300, 'skor_maksimum' => 319, 'tingkat_cefr' => 'B1-', 'label_tingkat' => 'Satisfactory', 'lulus' => true, 'deskripsi' => 'Lower Intermediate'],
            ['skor_minimum' => 280, 'skor_maksimum' => 299, 'tingkat_cefr' => 'A2+', 'label_tingkat' => 'Developing', 'lulus' => false, 'deskripsi' => 'Elementary Plus'],
            ['skor_minimum' => 260, 'skor_maksimum' => 279, 'tingkat_cefr' => 'A2', 'label_tingkat' => 'Basic', 'lulus' => false, 'deskripsi' => 'Elementary'],
            ['skor_minimum' => 240, 'skor_maksimum' => 259, 'tingkat_cefr' => 'A1+', 'label_tingkat' => 'Limited', 'lulus' => false, 'deskripsi' => 'High Beginner'],
            ['skor_minimum' => 200, 'skor_maksimum' => 239, 'tingkat_cefr' => 'A1', 'label_tingkat' => 'Beginning', 'lulus' => false, 'deskripsi' => 'Beginner'],
        ];
    }
}
