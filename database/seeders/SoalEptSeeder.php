<?php

namespace Database\Seeders;

use App\Models\BankSoal;
use App\Models\PartSoal;
use App\Models\ProgramTes;
use App\Models\Section;
use App\Models\Soal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SoalEptSeeder extends Seeder
{
    public function run(): void
    {
        $ept = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $listening = Section::where('program_tes_id', $ept->id)->where('nama', 'Listening')->firstOrFail();
        $reading = Section::where('program_tes_id', $ept->id)->where('nama', 'Reading')->firstOrFail();
        $data = require database_path('seeders/data/ept_questions.php');

        foreach (['Bank Soal Ujian Resmi', 'Bank Soal Ujian Tryout'] as $namaBank) {
            $bank = BankSoal::where('nama', $namaBank)->firstOrFail();

            if (Soal::where('bank_soal_id', $bank->id)->exists()) {
                $this->command->info("SoalEptSeeder dilewati — soal {$namaBank} sudah ada.");

                continue;
            }

            $this->seedBank($bank, $listening, $reading, $data);
        }

        $this->command->info('Soal EPT berhasil dibuat: 2 bank × 50 Listening dan 50 Reading.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function seedBank(BankSoal $bank, Section $listening, Section $reading, array $data): void
    {
        $partSoal = PartSoal::where('bank_soal_id', $bank->id)->get();

        $part = function (string $sectionId, string $nama) use ($partSoal): string {
            $hasil = $partSoal->first(
                fn (PartSoal $item): bool => $item->section_id === $sectionId && $item->nama === $nama,
            );

            if ($hasil === null) {
                throw new \RuntimeException("Part soal {$nama} tidak ditemukan.");
            }

            return $hasil->id;
        };

        $waktu = now();
        $audioLokasi = 'assets/audio/demo.mp3';
        $audioUkuran = is_file(storage_path('app/public/'.$audioLokasi))
            ? (int) filesize(storage_path('app/public/'.$audioLokasi))
            : null;

        $materiRows = [];
        $mediaRows = [];
        $soalRows = [];

        $urutanMembaca = 1;
        $urutanMenyimak = 1;

        $partMembacaPassage = $part($reading->id, 'Reading Part 1');
        $partMembacaIndividual = $part($reading->id, 'Reading Part 2');
        $partMenyimakIndividu = $part($listening->id, 'Listening Part 1');
        $partMenyimakPercakapan = $part($listening->id, 'Listening Part 2');
        $partMenyimakTalk = $part($listening->id, 'Listening Part 3');

        $buatMateri = function (string $judul, ?string $isiTeks, bool $denganAudio) use (
            &$materiRows,
            &$mediaRows,
            $bank,
            $waktu,
            $audioLokasi,
            $audioUkuran,
        ): string {
            $materiId = (string) Str::uuid();

            $materiRows[] = [
                'id' => $materiId,
                'bank_soal_id' => $bank->id,
                'judul' => $judul,
                'isi_teks' => $isiTeks,
                'created_at' => $waktu,
                'updated_at' => $waktu,
            ];

            if ($denganAudio) {
                $mediaRows[] = [
                    'id' => (string) Str::uuid(),
                    'materi_soal_id' => $materiId,
                    'jenis_media' => 'audio',
                    'lokasi_berkas' => $audioLokasi,
                    'nama_asli' => 'demo.mp3',
                    'tipe_mime' => 'audio/mpeg',
                    'ukuran_bita' => $audioUkuran,
                    'durasi_detik' => null,
                    'urutan' => 1,
                    'created_at' => $waktu,
                    'updated_at' => $waktu,
                ];
            }

            return $materiId;
        };

        $buatSoal = function (
            array $item,
            string $sectionId,
            ?string $materiSoalId,
            string $partSoalId,
            int $urutan,
        ) use (&$soalRows, $bank, $waktu): void {
            $soalRows[] = [
                'id' => (string) Str::uuid(),
                'bank_soal_id' => $bank->id,
                'materi_soal_id' => $materiSoalId,
                'section_id' => $sectionId,
                'part_soal_id' => $partSoalId,
                'teks_soal' => $item['q'],
                'pilihan_a' => $item['a'],
                'pilihan_b' => $item['b'],
                'pilihan_c' => $item['c'],
                'pilihan_d' => $item['d'],
                'kunci_jawaban' => $item['k'],
                'urutan' => $urutan,
                'created_at' => $waktu,
                'updated_at' => $waktu,
            ];
        };

        foreach ($data['reading_passages'] as $passage) {
            $materiId = $buatMateri($passage['judul'], $passage['isi_teks'], false);

            foreach ($passage['soal'] as $item) {
                $buatSoal($item, $reading->id, $materiId, $partMembacaPassage, $urutanMembaca++);
            }
        }

        foreach ($data['reading_individual'] as $item) {
            $buatSoal($item, $reading->id, null, $partMembacaIndividual, $urutanMembaca++);
        }

        foreach ($data['listening_short'] as $index => $item) {
            $materiId = $buatMateri('Short Conversation '.($index + 1), null, true);
            $buatSoal($item, $listening->id, $materiId, $partMenyimakIndividu, $urutanMenyimak++);
        }

        foreach ($data['listening_conversations'] as $conversation) {
            $materiId = $buatMateri($conversation['judul'], null, true);

            foreach ($conversation['soal'] as $item) {
                $buatSoal($item, $listening->id, $materiId, $partMenyimakPercakapan, $urutanMenyimak++);
            }
        }

        foreach ($data['listening_talks'] as $talk) {
            $materiId = $buatMateri($talk['judul'], null, true);

            foreach ($talk['soal'] as $item) {
                $buatSoal($item, $listening->id, $materiId, $partMenyimakTalk, $urutanMenyimak++);
            }
        }

        DB::table('materi_soal')->insert($materiRows);
        DB::table('media_materi_soal')->insert($mediaRows);
        DB::table('soal')->insert($soalRows);
    }
}
