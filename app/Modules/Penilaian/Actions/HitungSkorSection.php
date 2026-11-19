<?php

namespace App\Modules\Penilaian\Actions;

use App\Models\Jawaban;
use App\Models\Soal;
use App\Modules\Penilaian\DTOs\HasilSkorSection;
use App\Modules\Penilaian\Services\LayananKonversiSkor;
use Illuminate\Support\Collection;

class HitungSkorSection
{
    public function __construct(
        private LayananKonversiSkor $layananKonversi,
    ) {}

    /**
     * @param  array<string, mixed>  $aturan
     * @param  Collection<int, Soal>  $soalSection
     * @param  Collection<string, Jawaban>  $jawabanPerSoal
     */
    public function execute(
        array $aturan,
        Collection $soalSection,
        Collection $jawabanPerSoal,
        bool $simpanStatusJawaban = true,
    ): HasilSkorSection {
        $namaSection = (string) ($aturan['nama_section'] ?? $aturan['kode_section'] ?? 'Section');
        $skorMentahMaksimum = (int) ($aturan['skor_mentah_maksimum'] ?? 0);
        $jumlahSoal = $soalSection->count();

        if ($jumlahSoal === 0) {
            throw new \RuntimeException("Ujian belum memiliki soal untuk section {$namaSection}.");
        }

        if ($jumlahSoal > $skorMentahMaksimum) {
            throw new \RuntimeException(
                "Jumlah soal {$namaSection} ({$jumlahSoal}) melebihi skor mentah (Raw) maksimum ({$skorMentahMaksimum}).",
            );
        }

        $jumlahBenar = 0;
        $rincian = [];

        foreach ($soalSection as $soal) {
            $jawaban = $jawabanPerSoal->get($soal->id);
            $benar = $jawaban !== null && $jawaban->teks_jawaban === $soal->kunci_jawaban;

            if ($jawaban !== null && $simpanStatusJawaban) {
                $jawaban->update(['benar' => $benar]);
            }

            if ($benar) {
                $jumlahBenar++;
            }

            $rincian[] = [
                'soal_id' => $soal->id,
                'urutan' => $soal->urutan,
                'teks_jawaban' => $jawaban?->teks_jawaban,
                'kunci_jawaban' => $soal->kunci_jawaban,
                'benar' => $benar,
            ];
        }

        $skorSkala = $this->layananKonversi->konversi($aturan, $jumlahBenar);

        return new HasilSkorSection(
            sectionId: (string) $aturan['section_id'],
            kodeSection: (string) ($aturan['kode_section'] ?? ''),
            namaSection: $namaSection,
            jumlahBenar: $jumlahBenar,
            jumlahSoal: $jumlahSoal,
            skorSkala: $skorSkala,
            rincian: $rincian,
        );
    }
}
