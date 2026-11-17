<?php

namespace Database\Seeders;

use App\Models\BankSoal;
use App\Models\CatatanPelanggaran;
use App\Models\Fakultas;
use App\Models\HasilPenilaian;
use App\Models\PartUjian;
use App\Models\Pengguna;
use App\Models\PeriodePendaftaranUjian;
use App\Models\PeriodeUjian;
use App\Models\ProfilPeserta;
use App\Models\ProgramStudi;
use App\Models\ProgramTes;
use App\Models\Section;
use App\Models\SesiUjian;
use App\Models\SkemaPenilaian;
use App\Models\SlotUjian;
use App\Models\Soal;
use App\Models\Ujian;
use App\Modules\Penilaian\Services\LayananAturanPenilaian;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UjianSeeder extends Seeder
{
    public function run(): void
    {
        if (Ujian::where('judul', 'EPT Try Out 1')->exists()) {
            $this->command->info('UjianSeeder dilewati — data EPT sudah ada.');

            return;
        }

        $ept = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $bankSoal = BankSoal::where('nama', 'Bank Soal EPT')->firstOrFail();
        $sectionMenyimak = Section::where('program_tes_id', $ept->id)->where('nama', 'Listening')->firstOrFail();
        $sectionMembaca = Section::where('program_tes_id', $ept->id)->where('nama', 'Reading')->firstOrFail();

        $ujian = Ujian::create([
            'program_tes_id' => $ept->id,
            'judul' => 'EPT Try Out 1',
            'deskripsi' => 'Simulasi EPT dengan 50 soal Listening dan 50 soal Reading.',
            'mode' => 'tryout',
            'durasi_menit' => 120,
            'aktif' => true,
        ]);

        $partMenyimak = PartUjian::create([
            'ujian_id' => $ujian->id,
            'bank_soal_id' => $bankSoal->id,
            'section_id' => $sectionMenyimak->id,
            'judul' => 'Listening Section',
            'urutan' => 1,
            'jumlah_soal' => 50,
        ]);

        $partMembaca = PartUjian::create([
            'ujian_id' => $ujian->id,
            'bank_soal_id' => $bankSoal->id,
            'section_id' => $sectionMembaca->id,
            'judul' => 'Reading Section',
            'urutan' => 2,
            'jumlah_soal' => 50,
        ]);

        $this->lampirkanSoal($partMenyimak, $sectionMenyimak->id, $bankSoal->id);
        $this->lampirkanSoal($partMembaca, $sectionMembaca->id, $bankSoal->id);

        $mahasiswaDemo = Pengguna::firstOrCreate(
            ['email' => 'demo-peserta@toefl.test'],
            [
                'name' => 'Peserta Demo Proktor',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'aktif' => true,
            ]
        );
        $mahasiswaDemo->assignRole('peserta');

        $fkip = Fakultas::where('kode', 'FKIP')->first();
        $pbi = ProgramStudi::where('kode', 'PBI')->first();
        $admin = Pengguna::where('email', 'admin@toefl.test')->first();

        if ($fkip && $pbi && $admin) {
            ProfilPeserta::firstOrCreate(
                ['pengguna_id' => $mahasiswaDemo->id],
                [
                    'jenis_peserta' => 'mahasiswa',
                    'nomor_identitas' => '2200010099',
                    'fakultas_id' => $fkip->id,
                    'program_studi_id' => $pbi->id,
                    'tahun_mulai' => 2022,
                    'foto_identitas' => null,
                    'terverifikasi' => true,
                    'diverifikasi_pada' => now(),
                    'verifikator_id' => $admin->id,
                ]
            );
        }

        $skemaPenilaian = SkemaPenilaian::where('program_tes_id', $ept->id)->first();

        $snapshotPenilaian = $skemaPenilaian !== null
            ? app(LayananAturanPenilaian::class)->snapshot($skemaPenilaian)
            : null;

        $periodeUjian = PeriodeUjian::create([
            'ujian_id' => $ujian->id,
            'judul' => 'Gelombang Demo EPT',
            'tanggal_mulai' => now()->subDay(),
            'tanggal_selesai' => now()->addDay(),
            'aktif' => true,
        ]);

        PeriodePendaftaranUjian::create([
            'periode_ujian_id' => $periodeUjian->id,
            'nama' => 'Pendaftaran Try Out EPT',
            'dibuka_pada' => now()->subDay()->startOfDay(),
            'ditutup_pada' => now()->addDay()->endOfDay(),
            'aktif' => true,
            'catatan' => 'Data demo untuk pengelolaan periode pendaftaran dan slot peserta.',
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
            'part_ujian_aktif_id' => $partMenyimak->id,
            'skema_penilaian_id' => $skemaPenilaian?->id,
            'snapshot_penilaian' => $snapshotPenilaian,
            'skor_total_maksimum' => 400.0,
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
            'judul' => 'Gelombang Flagged EPT',
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
            'judul' => 'Gelombang Submitted EPT',
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

        $sesiDiserahkan = SesiUjian::create([
            'slot_ujian_id' => $slotDiserahkan->id,
            'pengguna_id' => $mahasiswaDemo->id,
            'status' => 'submitted',
            'dimulai_pada' => now()->subDays(1),
            'diserahkan_pada' => now()->subDays(1)->addMinutes(115),
            'skema_penilaian_id' => $skemaPenilaian?->id,
            'snapshot_penilaian' => $snapshotPenilaian,
            'jumlah_pelanggaran' => 0,
            'skor_membaca' => 186.0,
            'skor_menyimak' => 174.0,
            'skor_total' => 360.0,
            'skor_total_maksimum' => 400.0,
            'lulus' => true,
            'tingkat_cefr' => 'B2-',
            'label_tingkat' => 'Very Good',
            'deskripsi_tingkat' => 'Independent User',
        ]);

        if ($skemaPenilaian !== null) {
            HasilPenilaian::create([
                'sesi_ujian_id' => $sesiDiserahkan->id,
                'section_id' => $sectionMembaca->id,
                'skema_penilaian_id' => $skemaPenilaian->id,
                'jumlah_benar' => 43,
                'jumlah_soal' => 50,
                'skor_skala' => 186.0,
            ]);

            HasilPenilaian::create([
                'sesi_ujian_id' => $sesiDiserahkan->id,
                'section_id' => $sectionMenyimak->id,
                'skema_penilaian_id' => $skemaPenilaian->id,
                'jumlah_benar' => 37,
                'jumlah_soal' => 50,
                'skor_skala' => 174.0,
            ]);
        }
    }

    private function lampirkanSoal(PartUjian $part, string $sectionId, string $bankSoalId): void
    {
        $soal = Soal::where('bank_soal_id', $bankSoalId)
            ->where('section_id', $sectionId)
            ->orderBy('urutan')
            ->get();

        $baris = [];

        foreach ($soal as $urutan => $item) {
            $baris[] = [
                'id' => (string) Str::uuid(),
                'part_ujian_id' => $part->id,
                'soal_id' => $item->id,
                'urutan' => $urutan + 1,
            ];
        }

        if ($baris !== []) {
            DB::table('soal_part_ujian')->insert($baris);
        }

        $part->update(['jumlah_soal' => count($baris)]);
    }
}
