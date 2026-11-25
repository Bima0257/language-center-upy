<?php

namespace Database\Seeders;

use App\Models\BankSoal;
use App\Models\PartSoal;
use App\Models\ProgramTes;
use App\Models\Section;
use Illuminate\Database\Seeder;

class KelolaSoalTestingSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DataIndukSeeder::class);
        $this->call(KebijakanMateriSoalSeeder::class);

        $ept = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $listening = Section::where('program_tes_id', $ept->id)->where('nama', 'Listening')->firstOrFail();
        $reading = Section::where('program_tes_id', $ept->id)->where('nama', 'Reading')->firstOrFail();

        $bankUjiCoba = BankSoal::firstOrCreate(
            ['program_tes_id' => $ept->id, 'nama' => 'Bank Soal EPT - Uji Coba'],
            [
                'deskripsi' => 'Bank soal aktif tanpa soal untuk pengujian kelola bank dan part soal.',
                'aktif' => true,
            ],
        );

        $bankArsip = BankSoal::firstOrCreate(
            ['program_tes_id' => $ept->id, 'nama' => 'Bank Soal EPT - Arsip'],
            [
                'deskripsi' => 'Bank soal nonaktif untuk pengujian status dan filter bank soal.',
                'aktif' => false,
            ],
        );

        $daftarPart = [
            [$bankUjiCoba, $listening, 'Listening Part 1', 1, 'Listen to each short conversation and choose the best answer.', true],
            [$bankUjiCoba, $listening, 'Listening Part 2', 2, 'Listen to each longer conversation and answer the questions.', true],
            [$bankUjiCoba, $listening, 'Listening Part 3', 3, 'Listen to each talk and answer the questions.', false],
            [$bankUjiCoba, $reading, 'Reading Part 1', 1, 'Read each passage carefully and answer the questions.', true],
            [$bankArsip, $listening, 'Listening Part 1', 1, 'Listen to each short conversation and choose the best answer.', true],
            [$bankArsip, $reading, 'Reading Part 1', 1, 'Read each passage carefully and answer the questions.', true],
        ];

        foreach ($daftarPart as [$bankSoal, $section, $nama, $urutan, $petunjuk, $aktif]) {
            PartSoal::firstOrCreate(
                [
                    'bank_soal_id' => $bankSoal->id,
                    'section_id' => $section->id,
                    'nama' => $nama,
                ],
                [
                    'urutan' => $urutan,
                    'petunjuk' => $petunjuk,
                    'aktif' => $aktif,
                ],
            );
        }

        $jumlahPart = PartSoal::whereIn('bank_soal_id', [$bankUjiCoba->id, $bankArsip->id])->count();

        $this->command->info(
            "Seeder testing kelola soal selesai: bank '{$bankUjiCoba->nama}' dan '{$bankArsip->nama}' tersedia dengan {$jumlahPart} part.",
        );
    }
}
