<?php

namespace Database\Seeders;

use App\Models\BagianSoal;
use App\Models\BagianUjian;
use App\Models\BankSoal;
use App\Models\CatatanPelanggaran;
use App\Models\Fakultas;
use App\Models\JenisTes;
use App\Models\Keterampilan;
use App\Models\MateriSoal;
use App\Models\Pengguna;
use App\Models\PeriodeUjian;
use App\Models\ProfilMahasiswa;
use App\Models\ProgramStudi;
use App\Models\SesiUjian;
use App\Models\SlotUjian;
use App\Models\Soal;
use App\Models\Ujian;
use Illuminate\Database\Seeder;

class UjianSeeder extends Seeder
{
    public function run(): void
    {
        if (Ujian::where('judul', 'TOEFL iBT Try Out 1')->exists()) {
            $this->command->info('UjianSeeder dilewati — data demo sudah ada.');

            return;
        }

        $toefl = JenisTes::where('nama', 'TOEFL iBT')->firstOrFail();

        $ujian = Ujian::create([
            'jenis_tes_id' => $toefl->id,
            'judul' => 'TOEFL iBT Try Out 1',
            'deskripsi' => 'Try out TOEFL iBT full simulation — Reading & Listening sections.',
            'mode' => 'tryout',
            'durasi_menit' => 71,
            'aktif' => true,
        ]);

        $bankSoal = BankSoal::firstOrCreate(['nama' => 'Bank Soal Demo'], [
            'jenis_tes_id' => $toefl->id,
            'aktif' => true,
        ]);
        $bankSoal->update(['jenis_tes_id' => $toefl->id]);

        $keterampilanMembaca = Keterampilan::where('jenis_tes_id', $toefl->id)->where('nama', 'Reading')->firstOrFail();
        $keterampilanMenyimak = Keterampilan::where('jenis_tes_id', $toefl->id)->where('nama', 'Listening')->firstOrFail();

        $definisiBagian = [
            [$keterampilanMembaca->id, 'Part 1', 1],
            [$keterampilanMembaca->id, 'Part 2', 2],
            [$keterampilanMenyimak->id, 'Part 1', 1],
            [$keterampilanMenyimak->id, 'Part 2', 2],
            [$keterampilanMenyimak->id, 'Part 3', 3],
        ];

        foreach ($definisiBagian as [$keterampilanId, $nama, $urutan]) {
            BagianSoal::firstOrCreate(
                ['bank_soal_id' => $bankSoal->id, 'keterampilan_id' => $keterampilanId, 'nama' => $nama],
                ['urutan' => $urutan, 'aktif' => true],
            );
        }

        $bagianMembaca = BagianSoal::where('bank_soal_id', $bankSoal->id)->where('keterampilan_id', $keterampilanMembaca->id)->orderBy('urutan')->first();
        $bagianMenyimak = BagianSoal::where('bank_soal_id', $bankSoal->id)->where('keterampilan_id', $keterampilanMenyimak->id)->orderBy('urutan')->first();

        $membaca = BagianUjian::create([
            'ujian_id' => $ujian->id,
            'bank_soal_id' => $bankSoal->id,
            'keterampilan_id' => $keterampilanMembaca->id,
            'judul' => 'Reading Section',
            'urutan' => 1,
            'jumlah_soal' => 4,
        ]);

        $menyimak = BagianUjian::create([
            'ujian_id' => $ujian->id,
            'bank_soal_id' => $bankSoal->id,
            'keterampilan_id' => $keterampilanMenyimak->id,
            'judul' => 'Listening Section',
            'urutan' => 2,
            'jumlah_soal' => 4,
        ]);

        $materiMembaca = MateriSoal::create([
            'bank_soal_id' => $bankSoal->id,
            'judul' => 'The History of Solar Energy',
            'isi_teks' => 'Solar energy has been used by humans for thousands of years...',
        ]);

        $materiMenyimak = MateriSoal::create([
            'bank_soal_id' => $bankSoal->id,
            'judul' => 'Marine Biology',
            'isi_teks' => null,
        ]);

        $soalMembaca = [
            [
                'teks_soal' => 'What is the main topic of the passage?',
                'a' => 'The invention of solar panels in the 20th century',
                'b' => 'The history and development of solar energy',
                'c' => 'How ancient Greeks designed their buildings',
                'd' => 'The efficiency of modern solar cells',
                'jawaban' => 'B',
            ],
            [
                'teks_soal' => 'When was the photovoltaic effect first discovered?',
                'a' => '7th century BC',
                'b' => '1839',
                'c' => '1954',
                'd' => '2020',
                'jawaban' => 'B',
            ],
            [
                'teks_soal' => 'According to the passage, what efficiency did the first silicon solar cell achieve?',
                'a' => 'About 2%',
                'b' => 'About 6%',
                'c' => 'About 15%',
                'd' => 'About 22%',
                'jawaban' => 'B',
            ],
            [
                'teks_soal' => 'What does the passage imply about modern solar panels?',
                'a' => 'They are less efficient than early models',
                'b' => 'They were invented by the ancient Greeks',
                'c' => 'They are significantly more efficient than the first solar cell',
                'd' => 'They are no longer growing as an energy source',
                'jawaban' => 'C',
            ],
        ];

        $soalMenyimak = [
            [
                'teks_soal' => 'What is the lecture mainly about?',
                'a' => 'The differences between coral and algae',
                'b' => 'The relationship between coral reefs and their environment',
                'c' => 'How climate change affects ocean temperatures',
                'd' => 'Methods for preserving marine ecosystems',
                'jawaban' => 'B',
            ],
            [
                'teks_soal' => 'According to the professor, what causes coral bleaching?',
                'a' => 'An increase in marine predators',
                'b' => 'The loss of symbiotic algae due to warm water',
                'c' => 'Pollution from coastal development',
                'd' => 'Overfishing in reef areas',
                'jawaban' => 'B',
            ],
            [
                'teks_soal' => 'What does the professor say about the relationship between corals and algae?',
                'a' => 'Corals provide shelter, and algae provide food through photosynthesis',
                'b' => 'Algae compete with corals for space',
                'c' => 'Corals eat the algae for nutrition',
                'd' => 'Algae block sunlight from reaching the corals',
                'jawaban' => 'A',
            ],
            [
                'teks_soal' => 'What can be inferred about the professor view on coral reef preservation?',
                'a' => 'She believes it is too late to save most reefs',
                'b' => 'She thinks local efforts are more effective than global ones',
                'c' => 'She believes addressing climate change is essential',
                'd' => 'She thinks coral reefs can adapt to warmer temperatures',
                'jawaban' => 'C',
            ],
        ];

        foreach ($soalMembaca as $i => $q) {
            Soal::create([
                'bank_soal_id' => $bankSoal->id,
                'materi_soal_id' => $materiMembaca->id,
                'keterampilan_id' => $keterampilanMembaca->id,
                'bagian_soal_id' => $bagianMembaca?->id,
                'teks_soal' => $q['teks_soal'],
                'pilihan_a' => $q['a'],
                'pilihan_b' => $q['b'],
                'pilihan_c' => $q['c'],
                'pilihan_d' => $q['d'],
                'kunci_jawaban' => $q['jawaban'],
                'urutan' => $i + 1,
            ]);
        }

        foreach ($soalMenyimak as $i => $q) {
            Soal::create([
                'bank_soal_id' => $bankSoal->id,
                'materi_soal_id' => $materiMenyimak->id,
                'keterampilan_id' => $keterampilanMenyimak->id,
                'bagian_soal_id' => $bagianMenyimak?->id,
                'teks_soal' => $q['teks_soal'],
                'pilihan_a' => $q['a'],
                'pilihan_b' => $q['b'],
                'pilihan_c' => $q['c'],
                'pilihan_d' => $q['d'],
                'kunci_jawaban' => $q['jawaban'],
                'urutan' => $i + 1,
            ]);
        }

        $mahasiswaDemo = Pengguna::firstOrCreate(
            ['email' => 'demo-peserta@toefl.test'],
            [
                'name' => 'Peserta Demo Proktor',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'aktif' => true,
            ]
        );
        $mahasiswaDemo->assignRole('student');

        $fkip = Fakultas::where('kode', 'FKIP')->first();
        $pbi = ProgramStudi::where('kode', 'PBI')->first();
        $admin = Pengguna::where('email', 'admin@toefl.test')->first();

        if ($fkip && $pbi && $admin) {
            ProfilMahasiswa::firstOrCreate(
                ['pengguna_id' => $mahasiswaDemo->id],
                [
                    'nim' => '2200010099',
                    'fakultas_id' => $fkip->id,
                    'program_studi_id' => $pbi->id,
                    'tahun_angkatan' => 2022,
                    'foto_identitas' => null,
                    'terverifikasi' => true,
                    'diverifikasi_pada' => now(),
                    'verifikator_id' => $admin->id,
                ]
            );
        }

        $periodeUjian = PeriodeUjian::create([
            'ujian_id' => $ujian->id,
            'judul' => 'Gelombang Demo — Agustus 2026',
            'tanggal_mulai' => now()->subDay(),
            'tanggal_selesai' => now()->addDay(),
            'aktif' => true,
        ]);

        $slotUjian = SlotUjian::create([
            'periode_ujian_id' => $periodeUjian->id,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => now()->subHour()->format('H:i'),
            'waktu_selesai' => now()->addHours(2)->format('H:i'),
            'toleransi_keterlambatan_menit' => 15,
            'kapasitas_peserta' => 30,
            'aktif' => true,
        ]);

        $sesiAktif = SesiUjian::create([
            'slot_ujian_id' => $slotUjian->id,
            'pengguna_id' => $mahasiswaDemo->id,
            'status' => 'in_progress',
            'dimulai_pada' => now()->subMinutes(25),
            'bagian_aktif_id' => $membaca->id,
            'jumlah_pelanggaran' => 1,
        ]);

        CatatanPelanggaran::create([
            'sesi_ujian_id' => $sesiAktif->id,
            'jenis' => 'tab_switch',
            'tingkat' => 'minor',
            'deskripsi' => 'Peserta pindah tab ke aplikasi lain.',
            'jumlah_pelanggaran' => 1,
        ]);

        $periodeDitandai = PeriodeUjian::create([
            'ujian_id' => $ujian->id,
            'judul' => 'Gelombang Flagged — Agustus 2026',
            'tanggal_mulai' => now()->subDays(2),
            'tanggal_selesai' => now(),
            'aktif' => true,
        ]);

        $slotDitandai = SlotUjian::create([
            'periode_ujian_id' => $periodeDitandai->id,
            'tanggal' => now()->subDay()->toDateString(),
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '10:00',
            'toleransi_keterlambatan_menit' => 15,
            'kapasitas_peserta' => 30,
            'aktif' => true,
        ]);

        $sesiDitandai = SesiUjian::create([
            'slot_ujian_id' => $slotDitandai->id,
            'pengguna_id' => $mahasiswaDemo->id,
            'status' => 'terminated',
            'dimulai_pada' => now()->subHours(2),
            'dihentikan_pada' => now()->subHour(),
            'alasan_penghentian' => '3 strikes violation',
            'jumlah_pelanggaran' => 3,
            'ditandai' => true,
            'alasan_penandaan' => 'Pelanggaran mencapai 3 strike.',
        ]);

        CatatanPelanggaran::create([
            'sesi_ujian_id' => $sesiDitandai->id,
            'jenis' => 'tab_switch',
            'tingkat' => 'minor',
            'deskripsi' => 'Peserta pindah tab — strike 1.',
            'jumlah_pelanggaran' => 1,
        ]);

        CatatanPelanggaran::create([
            'sesi_ujian_id' => $sesiDitandai->id,
            'jenis' => 'fullscreen_exit',
            'tingkat' => 'minor',
            'deskripsi' => 'Peserta keluar dari mode layar penuh — strike 2.',
            'jumlah_pelanggaran' => 2,
        ]);

        CatatanPelanggaran::create([
            'sesi_ujian_id' => $sesiDitandai->id,
            'jenis' => 'tab_switch',
            'tingkat' => 'minor',
            'deskripsi' => 'Peserta pindah tab kembali — strike 3. Sesi dihentikan otomatis.',
            'jumlah_pelanggaran' => 3,
        ]);

        $periodeDiserahkan = PeriodeUjian::create([
            'ujian_id' => $ujian->id,
            'judul' => 'Gelombang Submitted — Agustus 2026',
            'tanggal_mulai' => now()->subDays(3),
            'tanggal_selesai' => now()->subDays(2),
            'aktif' => false,
        ]);

        $slotDiserahkan = SlotUjian::create([
            'periode_ujian_id' => $periodeDiserahkan->id,
            'tanggal' => now()->subDays(2)->toDateString(),
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '10:00',
            'toleransi_keterlambatan_menit' => 15,
            'kapasitas_peserta' => 30,
            'aktif' => true,
        ]);

        SesiUjian::create([
            'slot_ujian_id' => $slotDiserahkan->id,
            'pengguna_id' => $mahasiswaDemo->id,
            'status' => 'submitted',
            'dimulai_pada' => now()->subDays(1),
            'diserahkan_pada' => now()->subDays(1)->addMinutes(35),
            'jumlah_pelanggaran' => 0,
            'skor_membaca' => 22.5,
            'skor_menyimak' => 18.0,
            'skor_total' => 40.5,
        ]);

        // Kebijakan materi harus terisi untuk bank yang dibuat seeder ini (idempotent).
        $this->call(KebijakanMateriSoalSeeder::class);
    }
}
