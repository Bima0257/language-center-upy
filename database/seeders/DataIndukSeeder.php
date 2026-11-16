<?php

namespace Database\Seeders;

use App\Models\AturanPenilaian;
use App\Models\BankSoal;
use App\Models\Fakultas;
use App\Models\InterpretasiSkor;
use App\Models\PartSoal;
use App\Models\ProgramStudi;
use App\Models\ProgramTes;
use App\Models\Section;
use App\Models\SkemaPenilaian;
use App\Modules\DataInduk\Services\LayananDataInduk;
use Illuminate\Database\Seeder;

class DataIndukSeeder extends Seeder
{
    public function run(): void
    {
        $layananDataInduk = app(LayananDataInduk::class);
        $ept = ProgramTes::where('nama', 'EPT')->first();

        if ($ept === null) {
            $ept = $layananDataInduk->buatProgramTes([
                'nama' => 'EPT',
                'batas_pelanggaran' => 3,
                'deskripsi' => 'English Proficiency Test',
                'aktif' => true,
            ]);
        }

        $bankEpt = BankSoal::firstOrCreate(
            ['nama' => 'Bank Soal EPT'],
            [
                'program_tes_id' => $ept->id,
                'deskripsi' => 'Kumpulan soal EPT untuk pelaksanaan ujian',
                'aktif' => true,
            ],
        );

        $bankLatihan = BankSoal::firstOrCreate(
            ['nama' => 'Bank Soal Latihan'],
            [
                'program_tes_id' => $ept->id,
                'deskripsi' => 'Bank soal latihan untuk pengelolaan admin',
                'aktif' => true,
            ],
        );

        $sectionMenyimak = Section::where('program_tes_id', $ept->id)->where('nama', 'Listening')->first();
        if ($sectionMenyimak === null) {
            $sectionMenyimak = $layananDataInduk->buatSection([
                'program_tes_id' => $ept->id,
                'nama' => 'Listening',
                'urutan' => 1,
                'aktif' => true,
            ]);
        }

        $sectionMembaca = Section::where('program_tes_id', $ept->id)->where('nama', 'Reading')->first();
        if ($sectionMembaca === null) {
            $sectionMembaca = $layananDataInduk->buatSection([
                'program_tes_id' => $ept->id,
                'nama' => 'Reading',
                'urutan' => 2,
                'aktif' => true,
            ]);
        }

        $definisiPart = [
            [
                $sectionMenyimak->id,
                'Listening Part 1',
                1,
                'Listen to each short conversation and choose the best answer.',
            ],
            [
                $sectionMenyimak->id,
                'Listening Part 2',
                2,
                'Listen to each longer conversation and answer the questions.',
            ],
            [
                $sectionMenyimak->id,
                'Listening Part 3',
                3,
                'Listen to each talk and answer the questions.',
            ],
            [
                $sectionMembaca->id,
                'Reading Part 1',
                1,
                'Read each passage carefully and answer the questions.',
            ],
            [
                $sectionMembaca->id,
                'Reading Part 2',
                2,
                'Choose the best answer to complete each sentence.',
            ],
        ];

        foreach ([$bankEpt, $bankLatihan] as $bankSoal) {
            foreach ($definisiPart as [$sectionId, $nama, $urutan, $petunjuk]) {
                PartSoal::firstOrCreate(
                    ['bank_soal_id' => $bankSoal->id, 'section_id' => $sectionId, 'nama' => $nama],
                    ['urutan' => $urutan, 'petunjuk' => $petunjuk, 'aktif' => true],
                );
            }
        }

        $fkip = Fakultas::firstOrCreate(['kode' => 'FKIP'], ['nama' => 'Fakultas Keguruan dan Ilmu Pendidikan', 'aktif' => true]);
        $ft = Fakultas::firstOrCreate(['kode' => 'FT'], ['nama' => 'Fakultas Teknik', 'aktif' => true]);

        ProgramStudi::firstOrCreate(['fakultas_id' => $fkip->id, 'kode' => 'PBI'], ['nama' => 'Pendidikan Bahasa Inggris', 'aktif' => true]);
        ProgramStudi::firstOrCreate(['fakultas_id' => $fkip->id, 'kode' => 'PMAT'], ['nama' => 'Pendidikan Matematika', 'aktif' => true]);
        ProgramStudi::firstOrCreate(['fakultas_id' => $ft->id, 'kode' => 'TI'], ['nama' => 'Teknik Informatika', 'aktif' => true]);

        $skemaPenilaian = SkemaPenilaian::firstOrCreate(
            ['program_tes_id' => $ept->id, 'versi' => 1],
            [
                'nama' => 'Skema '.$ept->nama.' v1',
                'status' => 'aktif',
                'metode_total' => 'jumlah',
                'diaktifkan_pada' => now(),
            ],
        );

        $tabelKonversi = [];
        for ($skorMentah = 0; $skorMentah <= 50; $skorMentah++) {
            $tabelKonversi[(string) $skorMentah] = 100 + ($skorMentah * 2);
        }

        foreach ([$sectionMembaca, $sectionMenyimak] as $section) {
            AturanPenilaian::updateOrCreate(
                [
                    'skema_penilaian_id' => $skemaPenilaian->id,
                    'section_id' => $section->id,
                ],
                [
                    'program_tes_id' => $ept->id,
                    'kode_section' => $section->kode,
                    'tabel_konversi' => $tabelKonversi,
                    'skor_mentah_maksimum' => 50,
                    'skor_skala_minimum' => 100,
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
            InterpretasiSkor::updateOrCreate(
                [
                    'skema_penilaian_id' => $skemaPenilaian->id,
                    'skor_minimum' => $interpretasi['min'],
                ],
                [
                    'program_tes_id' => $ept->id,
                    'skor_maksimum' => $interpretasi['max'],
                    'tingkat_cefr' => $interpretasi['cefr'],
                    'label_tingkat' => $interpretasi['label'],
                    'lulus' => $interpretasi['passing'],
                    'deskripsi' => $interpretasi['desc'],
                ],
            );
        }

        $aturanTersimpan = AturanPenilaian::where('skema_penilaian_id', $skemaPenilaian->id)->get();

        $skemaPenilaian->update([
            'skala_total_minimum' => $aturanTersimpan->sum('skor_skala_minimum'),
            'skala_total_maksimum' => $aturanTersimpan->sum('skor_skala_maksimum'),
        ]);
    }
}
