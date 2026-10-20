<?php

namespace Database\Seeders;

use App\Models\AturanPenilaian;
use App\Models\BagianSoal;
use App\Models\BankSoal;
use App\Models\Fakultas;
use App\Models\InterpretasiSkor;
use App\Models\JenisTes;
use App\Models\Keterampilan;
use App\Models\ProgramStudi;
use App\Modules\DataInduk\Services\LayananDataInduk;
use Illuminate\Database\Seeder;

class DataIndukSeeder extends Seeder
{
    public function run(): void
    {
        $layananDataInduk = app(LayananDataInduk::class);
        $toefl = JenisTes::where('nama', 'TOEFL iBT')->first();

        if ($toefl === null) {
            $toefl = $layananDataInduk->buatJenisTes([
                'nama' => 'TOEFL iBT',
                'batas_pelanggaran' => 3,
                'deskripsi' => 'Test of English as a Foreign Language',
                'aktif' => true,
            ]);
        }

        $bank2022 = BankSoal::firstOrCreate(['nama' => 'Bank Soal 2022'], ['jenis_tes_id' => $toefl->id, 'deskripsi' => 'Kumpulan soal tryout tahun 2022', 'aktif' => true]);
        $bank2023 = BankSoal::firstOrCreate(['nama' => 'Bank Soal 2023'], ['jenis_tes_id' => $toefl->id, 'deskripsi' => 'Kumpulan soal tryout tahun 2023', 'aktif' => true]);

        $keterampilanMembaca = Keterampilan::where('jenis_tes_id', $toefl->id)->where('nama', 'Reading')->first();
        if ($keterampilanMembaca === null) {
            $keterampilanMembaca = $layananDataInduk->buatKeterampilan([
                'jenis_tes_id' => $toefl->id,
                'nama' => 'Reading',
                'urutan' => 1,
                'aktif' => true,
            ]);
        }

        $keterampilanMenyimak = Keterampilan::where('jenis_tes_id', $toefl->id)->where('nama', 'Listening')->first();
        if ($keterampilanMenyimak === null) {
            $keterampilanMenyimak = $layananDataInduk->buatKeterampilan([
                'jenis_tes_id' => $toefl->id,
                'nama' => 'Listening',
                'urutan' => 2,
                'aktif' => true,
            ]);
        }

        $definisiBagian = [
            [$keterampilanMembaca->id, 'Part 1', 1, 'Read each passage carefully. Answer questions based on the information given in the passage.'],
            [$keterampilanMembaca->id, 'Part 2', 2, 'A word or phrase is missing in each of the sentences below. Select the best answer to complete the sentence.'],
            [$keterampilanMenyimak->id, 'Part 1', 1, 'Listen to each short conversation and question. Select the best answer to each question based on what is stated or implied by the speakers.'],
            [$keterampilanMenyimak->id, 'Part 2', 2, 'Listen to each longer conversation or talk. Answer the questions based on the information you hear.'],
            [$keterampilanMenyimak->id, 'Part 3', 3, 'Listen to each lecture. Answer the questions based on the information presented in the lecture.'],
        ];

        foreach ([$bank2022, $bank2023] as $bankSoal) {
            foreach ($definisiBagian as [$keterampilanId, $nama, $urutan, $petunjuk]) {
                BagianSoal::firstOrCreate(
                    ['bank_soal_id' => $bankSoal->id, 'keterampilan_id' => $keterampilanId, 'nama' => $nama],
                    ['urutan' => $urutan, 'petunjuk' => $petunjuk, 'aktif' => true],
                );
            }
        }

        $fkip = Fakultas::firstOrCreate(['kode' => 'FKIP'], ['nama' => 'Fakultas Keguruan dan Ilmu Pendidikan', 'aktif' => true]);
        $ft = Fakultas::firstOrCreate(['kode' => 'FT'], ['nama' => 'Fakultas Teknik', 'aktif' => true]);

        ProgramStudi::firstOrCreate(['fakultas_id' => $fkip->id, 'kode' => 'PBI'], ['nama' => 'Pendidikan Bahasa Inggris', 'aktif' => true]);
        ProgramStudi::firstOrCreate(['fakultas_id' => $fkip->id, 'kode' => 'PMAT'], ['nama' => 'Pendidikan Matematika', 'aktif' => true]);
        ProgramStudi::firstOrCreate(['fakultas_id' => $ft->id, 'kode' => 'TI'], ['nama' => 'Teknik Informatika', 'aktif' => true]);

        // Conversion table: raw 0-50 -> scaled 100-200 (2x + 100)
        $tabelKonversi = [];
        for ($skorMentah = 0; $skorMentah <= 50; $skorMentah++) {
            $tabelKonversi[(string) $skorMentah] = 100 + ($skorMentah * 2);
        }

        foreach ([$keterampilanMembaca, $keterampilanMenyimak] as $keterampilan) {
            AturanPenilaian::firstOrCreate(
                ['jenis_tes_id' => $toefl->id, 'kode_keterampilan' => $keterampilan->kode],
                [
                    'tabel_konversi' => $tabelKonversi,
                    'skor_mentah_maksimum' => 50,
                    'skor_skala_maksimum' => 200,
                ],
            );
        }

        $interpretasiSkor = [
            ['min' => 380, 'max' => 400, 'cefr' => 'B2', 'label' => 'Excellent', 'passing' => true, 'desc' => 'Very Good User'],
            ['min' => 360, 'max' => 379, 'cefr' => 'B2-', 'label' => 'Very Good', 'passing' => true, 'desc' => 'Independent User'],
            ['min' => 340, 'max' => 359, 'cefr' => 'B1+', 'label' => 'Good', 'passing' => true, 'desc' => 'Upper Intermediate'],
            ['min' => 320, 'max' => 339, 'cefr' => 'B1', 'label' => 'Competent', 'passing' => true, 'desc' => 'Intermediate'],
            ['min' => 300, 'max' => 319, 'cefr' => 'B1-', 'label' => 'Satisfactory', 'passing' => true, 'desc' => 'Lower Intermediate'],
            ['min' => 280, 'max' => 299, 'cefr' => 'A2+', 'label' => 'Developing', 'passing' => false, 'desc' => 'Elementary Plus'],
            ['min' => 260, 'max' => 279, 'cefr' => 'A2', 'label' => 'Basic', 'passing' => false, 'desc' => 'Elementary'],
            ['min' => 240, 'max' => 259, 'cefr' => 'A1+', 'label' => 'Limited', 'passing' => false, 'desc' => 'High Beginner'],
            ['min' => 200, 'max' => 239, 'cefr' => 'A1', 'label' => 'Beginning', 'passing' => false, 'desc' => 'Beginner'],
        ];

        foreach ($interpretasiSkor as $interpretasi) {
            InterpretasiSkor::firstOrCreate(
                ['jenis_tes_id' => $toefl->id, 'skor_minimum' => $interpretasi['min']],
                [
                    'skor_maksimum' => $interpretasi['max'],
                    'tingkat_cefr' => $interpretasi['cefr'],
                    'label_tingkat' => $interpretasi['label'],
                    'lulus' => $interpretasi['passing'],
                    'deskripsi' => $interpretasi['desc'],
                ],
            );
        }
    }
}
