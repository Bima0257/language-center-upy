<?php

namespace Tests\Feature\Ujian;

use App\Enums\ModeUjian;
use App\Models\BagianUjian;
use App\Models\BankSoal;
use App\Models\JenisTes;
use App\Models\Keterampilan;
use App\Models\MateriSoal;
use App\Models\Pengguna;
use App\Models\PeriodeUjian;
use App\Models\SesiUjian;
use App\Models\SlotUjian;
use App\Models\Soal;
use App\Models\Ujian;
use App\Modules\Penjadwalan\Repositories\Contracts\SlotUjianRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UjianMahasiswaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function masukSebagaiMahasiswa(): void
    {
        $pengguna = Pengguna::where('email', 'student@toefl.test')->first();

        $this->post('/login', [
            'email' => $pengguna->email,
            'password' => 'password',
        ]);
    }

    private function berkasUji(string $namaBerkas): UploadedFile
    {
        $berkasSementara = tempnam(sys_get_temp_dir(), 'test_jpeg_');
        file_put_contents($berkasSementara, base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAQgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAAyACgDASIAAhEBAxEB/8QAGAABAQEBAQAAAAAAAAAAAAAAAAUGBAf/xAAsEAABAwMCAwcFAAAAAAAAAAABAAIDBBEhBRIxBhNBUWFxgZGhFCIycfD/xAAYAQADAQEAAAAAAAAAAAAAAAABAgMABP/EABsRAQEAAgMBAAAAAAAAAAAAAAEAAhEDITES/9oADAMBAAIRAxEAPwD0yC2urKltNTxGSR2waPufAekUdZT1kPnU8zJGf1Ndf8AIQHUVdT4hS+TObRvBjkb1Y4cD+D0IjYpKC8b+3t54nAO/teA62h+P6W6tLZk7FBtK8ONi3l2OxB2I+yIAAAAAAAAAAAAAAAAAAAAAAAAAAAAf/9k='));

        return new UploadedFile($berkasSementara, $namaBerkas, 'image/jpeg', null, true);
    }

    private function buatUjian(string $judul, string $mode): Ujian
    {
        $jenisTes = JenisTes::firstOrCreate(['nama' => 'TOEFL iBT'], [
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);

        return Ujian::create([
            'jenis_tes_id' => $jenisTes->id,
            'judul' => $judul,
            'mode' => $mode,
            'durasi_menit' => 60,
            'aktif' => true,
        ]);
    }

    public function test_slot_tersedia_hanya_memuat_tryout(): void
    {
        $tryout = $this->buatUjian('Tryout Premium', ModeUjian::TRYOUT->value);
        $official = $this->buatUjian('Ujian Resmi', ModeUjian::OFFICIAL->value);

        $periodeTryout = PeriodeUjian::create([
            'ujian_id' => $tryout->id,
            'judul' => 'Gelombang Tryout',
            'tanggal_mulai' => now()->subDay(),
            'tanggal_selesai' => now()->addDay(),
            'aktif' => true,
        ]);

        $periodeResmi = PeriodeUjian::create([
            'ujian_id' => $official->id,
            'judul' => 'Gelombang Resmi',
            'tanggal_mulai' => now()->subDay(),
            'tanggal_selesai' => now()->addDay(),
            'aktif' => true,
        ]);

        SlotUjian::create([
            'periode_ujian_id' => $periodeTryout->id,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => now()->subMinutes(5)->format('H:i:s'),
            'waktu_selesai' => now()->addHours(2)->format('H:i:s'),
            'toleransi_keterlambatan_menit' => 15,
            'kapasitas_peserta' => 30,
            'aktif' => true,
        ]);

        SlotUjian::create([
            'periode_ujian_id' => $periodeResmi->id,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => now()->subMinutes(5)->format('H:i:s'),
            'waktu_selesai' => now()->addHours(2)->format('H:i:s'),
            'toleransi_keterlambatan_menit' => 15,
            'kapasitas_peserta' => 30,
            'aktif' => true,
        ]);

        $this->masukSebagaiMahasiswa();
        $this->get('/ujian/tersedia')->assertOk();

        $daftarSlotUjian = app(SlotUjianRepositoryInterface::class)->slotTersedia();

        $this->assertCount(1, $daftarSlotUjian);
        $this->assertEquals($tryout->id, $daftarSlotUjian->first()->periodeUjian->ujian_id);
    }

    public function test_jadwal_hanya_memuat_ujian_resmi(): void
    {
        $tryout = $this->buatUjian('Tryout Premium', ModeUjian::TRYOUT->value);
        $official = $this->buatUjian('Ujian Resmi', ModeUjian::OFFICIAL->value);

        PeriodeUjian::create([
            'ujian_id' => $tryout->id,
            'judul' => 'Gelombang Tryout',
            'tanggal_mulai' => now()->subDay(),
            'tanggal_selesai' => now()->addDay(),
            'aktif' => true,
        ]);

        PeriodeUjian::create([
            'ujian_id' => $official->id,
            'judul' => 'Gelombang Resmi',
            'tanggal_mulai' => now()->subDay(),
            'tanggal_selesai' => now()->addDay(),
            'aktif' => true,
        ]);

        $this->masukSebagaiMahasiswa();
        $this->get('/ujian/jadwal')->assertOk();

        $daftarPeriodeUjian = PeriodeUjian::where('aktif', true)
            ->where('tanggal_selesai', '>=', now()->toDateString())
            ->whereHas('ujian', fn ($q) => $q->where('mode', ModeUjian::OFFICIAL))
            ->get();

        $this->assertCount(1, $daftarPeriodeUjian);
        $this->assertEquals($official->id, $daftarPeriodeUjian->first()->ujian_id);
    }

    public function test_pengerjaan_sesi_menyembunyikan_kunci_jawaban(): void
    {
        $jenisTes = JenisTes::firstOrCreate(['nama' => 'TOEFL iBT'], [
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);

        $bankSoal = BankSoal::firstOrCreate(
            ['nama' => 'Bank Ujian Resmi'],
            ['jenis_tes_id' => $jenisTes->id, 'aktif' => true],
        );

        $keterampilan = Keterampilan::firstOrCreate(
            ['jenis_tes_id' => $jenisTes->id, 'nama' => 'Reading'],
            [
                'kode' => '001001',
                'nama' => 'Reading',
                'jenis_tes_id' => $jenisTes->id,
                'urutan' => 1,
                'aktif' => true,
            ],
        );

        $materiSoal = MateriSoal::create([
            'bank_soal_id' => $bankSoal->id,
            'judul' => 'Passage untuk Ujian',
            'isi_teks' => '<p>Isi passage ujian resmi.</p>',
        ]);

        $soal = Soal::create([
            'bank_soal_id' => $bankSoal->id,
            'materi_soal_id' => $materiSoal->id,
            'keterampilan_id' => $keterampilan->id,
            'teks_soal' => 'Apa isi passage tersebut?',
            'pilihan_a' => 'Pilihan A',
            'pilihan_b' => 'Pilihan B',
            'pilihan_c' => 'Pilihan C',
            'pilihan_d' => 'Pilihan D',
            'kunci_jawaban' => 'A',
            'urutan' => 1,
            'status' => 'approved',
        ]);

        $ujian = Ujian::create([
            'jenis_tes_id' => $jenisTes->id,
            'judul' => 'Ujian Resmi untuk Take Test',
            'mode' => ModeUjian::OFFICIAL->value,
            'durasi_menit' => 60,
            'aktif' => true,
        ]);

        $bagianUjian = BagianUjian::create([
            'ujian_id' => $ujian->id,
            'bank_soal_id' => $bankSoal->id,
            'keterampilan_id' => $keterampilan->id,
            'judul' => 'Reading — Bank Ujian Resmi',
            'urutan' => 1,
            'jumlah_soal' => 1,
        ]);

        $bagianUjian->soalBagianUjian()->create([
            'soal_id' => $soal->id,
            'urutan' => 1,
        ]);

        $periodeUjian = PeriodeUjian::create([
            'ujian_id' => $ujian->id,
            'judul' => 'Periode Take Test',
            'tanggal_mulai' => now()->subDay(),
            'tanggal_selesai' => now()->addDay(),
            'aktif' => true,
        ]);

        $slotUjian = SlotUjian::create([
            'periode_ujian_id' => $periodeUjian->id,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => now()->subMinutes(5)->format('H:i:s'),
            'waktu_selesai' => now()->addHours(2)->format('H:i:s'),
            'toleransi_keterlambatan_menit' => 15,
            'kapasitas_peserta' => 30,
            'aktif' => true,
        ]);

        $this->masukSebagaiMahasiswa();

        SesiUjian::where('pengguna_id', Pengguna::where('email', 'student@toefl.test')->first()->id)
            ->where('status', 'in_progress')
            ->delete();

        Storage::fake('public');

        $lokasiSwafoto = 'selfies/test-selfie.jpg';
        Storage::disk('public')->put($lokasiSwafoto, file_get_contents('data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAAyACgDASIAAhEBAxEB/8QAGAABAQEBAQAAAAAAAAAAAAAAAAUGBAf/xAAsEAABAwMCAwcFAAAAAAAAAAABAAIDBBEhBRIxBhNBUWFxgZGhFCIycfD/xAAYAQADAQEAAAAAAAAAAAAAAAABAgMABP/EABsRAQEAAgMBAAAAAAAAAAAAAAEAAhEDITES/9oADAMBAAIRAxEAPwD0yC2urKltNTxGSR2waPufAekUdZT1kPnU8zJGf1Ndf8AIQHUVdT4hS+TObRvBjkb1Y4cD+D0IjYpKC8b+3t54nAO/teA62h+P6W6tLZk7FBtK8ONi3l2OxB2I+yIAAAAAAAAAAAAAAAAAAAAAAAAAAAAf/9k='));
        $responsMulai = $this->post("/ujian/slot/{$slotUjian->id}/mulai", [
            'jenis_perangkat' => 'desktop',
            'agen_pengguna_perangkat' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'swafoto' => $this->berkasUji('selfie.jpg'),
        ]);
        $responsMulai->assertRedirect();

        $sesiUjian = SesiUjian::where('pengguna_id', Pengguna::where('email', 'student@toefl.test')->first()->id)
            ->where('slot_ujian_id', $slotUjian->id)
            ->first();

        $this->assertNotNull($sesiUjian);

        $response = $this->get("/ujian/sesi/{$sesiUjian->id}");
        $response->assertOk();

        $response->assertDontSee('kunci_jawaban', false);
    }

    public function test_mulai_ujian_menolak_perangkat_seluler_atau_tablet(): void
    {
        $jenisTes = JenisTes::firstOrCreate(['nama' => 'TOEFL iBT'], [
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);

        $bankSoal = BankSoal::firstOrCreate(
            ['nama' => 'Bank Ujian Resmi'],
            ['jenis_tes_id' => $jenisTes->id, 'aktif' => true],
        );

        $keterampilan = Keterampilan::firstOrCreate(
            ['jenis_tes_id' => $jenisTes->id, 'nama' => 'Reading'],
            [
                'kode' => '001001',
                'nama' => 'Reading',
                'jenis_tes_id' => $jenisTes->id,
                'urutan' => 1,
                'aktif' => true,
            ],
        );

        $materiSoal = MateriSoal::create([
            'bank_soal_id' => $bankSoal->id,
            'judul' => 'Passage untuk Ujian',
            'isi_teks' => '<p>Isi passage ujian resmi.</p>',
        ]);

        $soal = Soal::create([
            'bank_soal_id' => $bankSoal->id,
            'materi_soal_id' => $materiSoal->id,
            'keterampilan_id' => $keterampilan->id,
            'teks_soal' => 'Apa isi passage tersebut?',
            'pilihan_a' => 'Pilihan A',
            'pilihan_b' => 'Pilihan B',
            'pilihan_c' => 'Pilihan C',
            'pilihan_d' => 'Pilihan D',
            'kunci_jawaban' => 'A',
            'urutan' => 1,
            'status' => 'approved',
        ]);

        $ujian = Ujian::create([
            'jenis_tes_id' => $jenisTes->id,
            'judul' => 'Ujian Resmi untuk Device Test',
            'mode' => ModeUjian::OFFICIAL->value,
            'durasi_menit' => 60,
            'aktif' => true,
        ]);

        $bagianUjian = BagianUjian::create([
            'ujian_id' => $ujian->id,
            'bank_soal_id' => $bankSoal->id,
            'keterampilan_id' => $keterampilan->id,
            'judul' => 'Reading — Bank Ujian Resmi',
            'urutan' => 1,
            'jumlah_soal' => 1,
        ]);

        $bagianUjian->soalBagianUjian()->create([
            'soal_id' => $soal->id,
            'urutan' => 1,
        ]);

        $periodeUjian = PeriodeUjian::create([
            'ujian_id' => $ujian->id,
            'judul' => 'Periode Device Test',
            'tanggal_mulai' => now()->subDay(),
            'tanggal_selesai' => now()->addDay(),
            'aktif' => true,
        ]);

        $slotUjian = SlotUjian::create([
            'periode_ujian_id' => $periodeUjian->id,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => now()->subMinutes(5)->format('H:i:s'),
            'waktu_selesai' => now()->addHours(2)->format('H:i:s'),
            'toleransi_keterlambatan_menit' => 15,
            'kapasitas_peserta' => 30,
            'aktif' => true,
        ]);

        $this->masukSebagaiMahasiswa();

        SesiUjian::where('pengguna_id', Pengguna::where('email', 'student@toefl.test')->first()->id)
            ->where('status', 'in_progress')
            ->delete();

        $lokasiSwafoto = 'selfies/test-selfie-mobile.jpg';
        Storage::disk('public')->put($lokasiSwafoto, file_get_contents('data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAQgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAAyACgDASIAAhEBAxEB/8QAGAABAQEBAQAAAAAAAAAAAAAAAAUGBAf/xAAsEAABAwMCAwcFAAAAAAAAAAABAAIDBBEhBRIxBhNBUWFxgZGhFCIycfD/xAAYAQADAQEAAAAAAAAAAAAAAAABAgMABP/EABsRAQEAAgMBAAAAAAAAAAAAAAEAAhEDITES/9oADAMBAAIRAxEAPwD0yC2urKltNTxGSR2waPufAekUdZT1kPnU8zJGf1Ndf8AIQHUVdT4hS+TObRvBjkb1Y4cD+D0IjYpKC8b+3t54nAO/teA62h+P6W6tLZk7FBtK8ONi3l2OxB2I+yIAAAAAAAAAAAAAAAAAAAAAAAAAAAAf/9k='));
        $responsMulai = $this->post("/ujian/slot/{$slotUjian->id}/mulai", [
            'jenis_perangkat' => 'mobile',
            'agen_pengguna_perangkat' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)',
            'swafoto' => $this->berkasUji('selfie.jpg'),
        ]);
        $responsMulai->assertSessionHas('error', 'Ujian hanya dapat diakses dari laptop atau komputer. HP dan tablet tidak diperbolehkan.');

        $sesiUjian = SesiUjian::where('pengguna_id', Pengguna::where('email', 'student@toefl.test')->first()->id)
            ->where('slot_ujian_id', $slotUjian->id)
            ->first();
        $this->assertNull($sesiUjian);
    }

    public function test_mulai_ujian_mewajibkan_foto_swafoto(): void
    {
        $jenisTes = JenisTes::firstOrCreate(['nama' => 'TOEFL iBT'], [
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);

        $bankSoal = BankSoal::firstOrCreate(
            ['nama' => 'Bank Ujian Resmi'],
            ['jenis_tes_id' => $jenisTes->id, 'aktif' => true],
        );

        $keterampilan = Keterampilan::firstOrCreate(
            ['jenis_tes_id' => $jenisTes->id, 'nama' => 'Reading'],
            [
                'kode' => '001001',
                'nama' => 'Reading',
                'jenis_tes_id' => $jenisTes->id,
                'urutan' => 1,
                'aktif' => true,
            ],
        );

        $materiSoal = MateriSoal::create([
            'bank_soal_id' => $bankSoal->id,
            'judul' => 'Passage untuk Ujian',
            'isi_teks' => '<p>Isi passage ujian resmi.</p>',
        ]);

        $soal = Soal::create([
            'bank_soal_id' => $bankSoal->id,
            'materi_soal_id' => $materiSoal->id,
            'keterampilan_id' => $keterampilan->id,
            'teks_soal' => 'Apa isi passage tersebut?',
            'pilihan_a' => 'Pilihan A',
            'pilihan_b' => 'Pilihan B',
            'pilihan_c' => 'Pilihan C',
            'pilihan_d' => 'Pilihan D',
            'kunci_jawaban' => 'A',
            'urutan' => 1,
            'status' => 'approved',
        ]);

        $ujian = Ujian::create([
            'jenis_tes_id' => $jenisTes->id,
            'judul' => 'Ujian Resmi untuk Selfie Test',
            'mode' => ModeUjian::OFFICIAL->value,
            'durasi_menit' => 60,
            'aktif' => true,
        ]);

        $bagianUjian = BagianUjian::create([
            'ujian_id' => $ujian->id,
            'bank_soal_id' => $bankSoal->id,
            'keterampilan_id' => $keterampilan->id,
            'judul' => 'Reading — Bank Ujian Resmi',
            'urutan' => 1,
            'jumlah_soal' => 1,
        ]);

        $bagianUjian->soalBagianUjian()->create([
            'soal_id' => $soal->id,
            'urutan' => 1,
        ]);

        $periodeUjian = PeriodeUjian::create([
            'ujian_id' => $ujian->id,
            'judul' => 'Periode Selfie Test',
            'tanggal_mulai' => now()->subDay(),
            'tanggal_selesai' => now()->addDay(),
            'aktif' => true,
        ]);

        $slotUjian = SlotUjian::create([
            'periode_ujian_id' => $periodeUjian->id,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => now()->subMinutes(5)->format('H:i:s'),
            'waktu_selesai' => now()->addHours(2)->format('H:i:s'),
            'toleransi_keterlambatan_menit' => 15,
            'kapasitas_peserta' => 30,
            'aktif' => true,
        ]);

        $this->masukSebagaiMahasiswa();

        SesiUjian::where('pengguna_id', Pengguna::where('email', 'student@toefl.test')->first()->id)
            ->where('status', 'in_progress')
            ->delete();

        $responsMulai = $this->post("/ujian/slot/{$slotUjian->id}/mulai", [
            'jenis_perangkat' => 'desktop',
            'agen_pengguna_perangkat' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);
        $responsMulai->assertSessionHasErrors('swafoto');
    }
}
