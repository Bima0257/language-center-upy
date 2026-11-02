<?php

namespace Tests\Feature\Ujian;

use App\Models\BagianSoal;
use App\Models\BankSoal;
use App\Models\JenisTes;
use App\Models\KebijakanMateriSoal;
use App\Models\Keterampilan;
use App\Models\MateriSoal;
use App\Models\Pengguna;
use App\Models\Soal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PustakaSoalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function masukSebagai(string $peran): Pengguna
    {
        $pengguna = Pengguna::where('email', $peran.'@toefl.test')->first();

        $this->post('/login', [
            'email' => $pengguna->email,
            'password' => 'password',
        ]);

        return $pengguna;
    }

    private function bankSoal(): BankSoal
    {
        return BankSoal::firstOrCreate(['nama' => 'Bank Soal 2022'], [
            'jenis_tes_id' => JenisTes::first()->id,
            'aktif' => true,
        ]);
    }

    private function bagianMembaca(): BagianSoal
    {
        $keterampilan = Keterampilan::where('nama', 'Reading')->first();

        return BagianSoal::where('bank_soal_id', $this->bankSoal()->id)
            ->where('keterampilan_id', $keterampilan->id)
            ->orderBy('urutan')
            ->first();
    }

    private function bagianMenyimak(): BagianSoal
    {
        $keterampilan = Keterampilan::where('nama', 'Listening')->first();

        return BagianSoal::where('bank_soal_id', $this->bankSoal()->id)
            ->where('keterampilan_id', $keterampilan->id)
            ->first();
    }

    private function bagianMembacaLain(): BagianSoal
    {
        $bagianMembaca = $this->bagianMembaca();

        return BagianSoal::create([
            'bank_soal_id' => $bagianMembaca->bank_soal_id,
            'keterampilan_id' => $bagianMembaca->keterampilan_id,
            'nama' => 'Bagian Membaca Alternatif',
            'urutan' => $bagianMembaca->urutan + 1,
            'aktif' => true,
        ]);
    }

    private function buatMateriTeks(?BankSoal $bankSoal = null): MateriSoal
    {
        return MateriSoal::create([
            'bank_soal_id' => ($bankSoal ?? $this->bankSoal())->id,
            'judul' => 'Passage Teks Uji',
            'isi_teks' => '<p>Isi passage teks.</p>',
        ]);
    }

    private function buatMateriAudio(?BankSoal $bankSoal = null): MateriSoal
    {
        $materiSoal = MateriSoal::create([
            'bank_soal_id' => ($bankSoal ?? $this->bankSoal())->id,
            'judul' => 'Passage Audio Uji',
            'isi_teks' => null,
        ]);

        $materiSoal->mediaMateriSoal()->create([
            'jenis_media' => 'audio',
            'lokasi_berkas' => 'passages/audio/test-audio.mp3',
            'urutan' => 0,
        ]);

        return $materiSoal;
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/pustaka-soal')->assertRedirect('/login');
    }

    public function test_tamu_diarahkan_ke_login_saat_mengakses_media_materi(): void
    {
        $this->get('/media/passages/audio/tidak-ditemukan.mp3')->assertRedirect('/login');
    }

    public function test_peran_tanpa_izin_ditolak(): void
    {
        $this->masukSebagai('student');

        $this->get('/pustaka-soal')->assertForbidden();
    }

    public function test_admin_dapat_mengakses_pustaka_soal(): void
    {
        $this->masukSebagai('admin');

        $this->get('/pustaka-soal')->assertOk();
    }

    public function test_pustaka_soal_memilih_keterampilan_dan_bagian_pertama_secara_default(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal 2022')->firstOrFail();
        $keterampilanPertama = Keterampilan::where('jenis_tes_id', $bankSoal->jenis_tes_id)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->firstOrFail();
        $bagianSoalPertama = BagianSoal::where('bank_soal_id', $bankSoal->id)
            ->where('keterampilan_id', $keterampilanPertama->id)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->firstOrFail();

        $page = $this->get('/pustaka-soal?bank_soal_id='.$bankSoal->id.'&jenis_tes_id='.$bankSoal->jenis_tes_id)
            ->assertOk()
            ->inertiaPage();

        $this->assertSame($bankSoal->id, $page['props']['bankSoalTerpilih']['id']);
        $this->assertSame($bankSoal->nama, $page['props']['bankSoalTerpilih']['nama']);
        $this->assertSame($keterampilanPertama->id, $page['props']['filter']['keterampilan']);
        $this->assertSame($bagianSoalPertama->id, $page['props']['filter']['bagian_soal_id']);
        $this->assertTrue(collect($page['props']['soal']['data'])->every(
            fn (array $soal): bool => $soal['keterampilan_id'] === $keterampilanPertama->id
                && $soal['bagian_soal_id'] === $bagianSoalPertama->id,
        ));

        $keterampilanLain = Keterampilan::where('jenis_tes_id', $bankSoal->jenis_tes_id)
            ->where('aktif', true)
            ->where('id', '!=', $keterampilanPertama->id)
            ->orderBy('urutan')
            ->firstOrFail();
        $bagianSoalLain = BagianSoal::where('bank_soal_id', $bankSoal->id)
            ->where('keterampilan_id', $keterampilanLain->id)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->firstOrFail();
        $filteredPage = $this->get(
            '/pustaka-soal?bank_soal_id='.$bankSoal->id
                .'&jenis_tes_id='.$bankSoal->jenis_tes_id
                .'&keterampilan='.$keterampilanLain->id
                .'&bagian_soal_id='.$bagianSoalLain->id,
        )->assertOk()->inertiaPage();

        $this->assertSame($keterampilanLain->id, $filteredPage['props']['filter']['keterampilan']);
        $this->assertSame($bagianSoalLain->id, $filteredPage['props']['filter']['bagian_soal_id']);
    }

    public function test_pustaka_soal_menentukan_bank_dari_filter_bagian_soal(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal 2022')->firstOrFail();
        $keterampilan = Keterampilan::where('jenis_tes_id', $bankSoal->jenis_tes_id)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->firstOrFail();
        $bagianSoal = BagianSoal::where('bank_soal_id', $bankSoal->id)
            ->where('keterampilan_id', $keterampilan->id)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->firstOrFail();

        $page = $this->get('/pustaka-soal?bagian_soal_id='.$bagianSoal->id)
            ->assertOk()
            ->inertiaPage();

        $this->assertSame($bagianSoal->bank_soal_id, $page['props']['bankSoalTerpilihId']);
        $this->assertSame($bagianSoal->bank_soal_id, $page['props']['filter']['bank_soal_id']);
        $this->assertSame($bagianSoal->keterampilan_id, $page['props']['filter']['keterampilan']);
        $this->assertSame($bagianSoal->id, $page['props']['filter']['bagian_soal_id']);
    }

    public function test_pustaka_soal_fallback_ke_keterampilan_aktif_jika_filter_tidak_valid(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal 2022')->firstOrFail();
        $keterampilanNonaktif = Keterampilan::where('jenis_tes_id', $bankSoal->jenis_tes_id)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->firstOrFail();
        $keterampilanNonaktif->update(['aktif' => false]);

        $keterampilanAktif = Keterampilan::where('jenis_tes_id', $bankSoal->jenis_tes_id)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->firstOrFail();

        $page = $this->get(
            '/pustaka-soal?bank_soal_id='.$bankSoal->id
                .'&jenis_tes_id='.$bankSoal->jenis_tes_id
                .'&keterampilan='.$keterampilanNonaktif->id,
        )->assertOk()->inertiaPage();

        $this->assertSame($keterampilanAktif->id, $page['props']['filter']['keterampilan']);
    }

    public function test_bulk_create_membawa_filter_keterampilan_dan_bagian_soal_dari_pustaka(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal 2022')->firstOrFail();
        $keterampilan = Keterampilan::where('jenis_tes_id', $bankSoal->jenis_tes_id)
            ->where('nama', 'Reading')
            ->where('aktif', true)
            ->firstOrFail();
        $bagianSoal = BagianSoal::where('bank_soal_id', $bankSoal->id)
            ->where('keterampilan_id', $keterampilan->id)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->firstOrFail();

        $page = $this->get(
            '/pustaka-soal/tambah?bank_soal_id='.$bankSoal->id
                .'&keterampilan='.$keterampilan->id
                .'&bagian_soal_id='.$bagianSoal->id,
        )->assertOk()->inertiaPage();

        $this->assertSame($bankSoal->id, $page['props']['bankSoalTerpilih']);
        $this->assertSame($keterampilan->id, $page['props']['filterKembali']['keterampilan']);
        $this->assertSame($bagianSoal->id, $page['props']['filterKembali']['bagian_soal_id']);
        $this->assertArrayHasKey('materiSoal', $page['props']);
        $this->assertTrue(collect($page['props']['materiSoal'])->every(
            fn (array $materi): bool => $materi['bank_soal_id'] === $bankSoal->id,
        ));
        $this->assertArrayNotHasKey('grupMateriSoal', $page['props']);
    }

    public function test_admin_dapat_menavigasi_hierarki_jenis_tes_dan_bank_soal(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal 2022')->firstOrFail();
        $jenisTes = $bankSoal->jenisTes;
        $jenisTesLain = JenisTes::create([
            'kode' => '010',
            'nama' => 'Jenis Tes Navigasi Lain',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);
        $bankSoalLain = BankSoal::create([
            'jenis_tes_id' => $jenisTesLain->id,
            'nama' => 'Bank Soal Navigasi Lain',
            'aktif' => true,
        ]);

        $root = $this->get('/pustaka-soal')
            ->assertOk()
            ->inertiaPage();
        $bank = $this->get('/pustaka-soal?jenis_tes_id='.$jenisTes->id)
            ->assertOk()
            ->inertiaPage();
        $detail = $this->get('/pustaka-soal?jenis_tes_id='.$jenisTes->id.'&bank_soal_id='.$bankSoal->id)
            ->assertOk()
            ->inertiaPage();

        $rootBank = collect($root['props']['bankSoal'])->firstWhere('id', $bankSoal->id);
        $bankIds = collect($bank['props']['bankSoal'])->pluck('id');

        $this->assertNull($root['props']['jenisTesTerpilihId']);
        $this->assertNull($root['props']['bankSoalTerpilihId']);
        $this->assertNotNull($rootBank);
        $this->assertSame($bankSoal->soal()->count(), (int) $rootBank['soal_count']);
        $this->assertSame($jenisTes->id, $bank['props']['jenisTesTerpilihId']);
        $this->assertTrue($bankIds->contains($bankSoal->id));
        $this->assertFalse($bankIds->contains($bankSoalLain->id));
        $this->assertSame($jenisTes->id, $detail['props']['jenisTesTerpilihId']);
        $this->assertSame($bankSoal->id, $detail['props']['bankSoalTerpilihId']);
    }

    public function test_admin_dapat_melihat_bank_soal_hanya_dari_kategori_terpilih(): void
    {
        $this->masukSebagai('admin');

        $jenisTes = JenisTes::create([
            'kode' => '010',
            'nama' => 'Kategori Bank Soal Detail',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);
        $jenisTesLain = JenisTes::create([
            'kode' => '011',
            'nama' => 'Kategori Bank Soal Lain',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);
        $bankSoalTerpilih = BankSoal::create([
            'jenis_tes_id' => $jenisTes->id,
            'nama' => 'Bank Soal Dalam Kategori',
            'aktif' => true,
        ]);
        $bankSoalLain = BankSoal::create([
            'jenis_tes_id' => $jenisTesLain->id,
            'nama' => 'Bank Soal Di Luar Kategori',
            'aktif' => true,
        ]);

        $page = $this->get("/pustaka-soal/bank-soal/kategori/{$jenisTes->id}")
            ->assertOk()
            ->inertiaPage();

        $bankSoalIds = collect($page['props']['bankSoal'])->pluck('id');

        $this->assertSame('BankSoal/KelolaBankSoal/index', $page['component']);
        $this->assertSame($jenisTes->id, $page['props']['kategori']['id']);
        $this->assertTrue($bankSoalIds->contains($bankSoalTerpilih->id));
        $this->assertFalse($bankSoalIds->contains($bankSoalLain->id));
    }

    public function test_bank_soal_tidak_dapat_dipindah_ke_jenis_tes_nonaktif(): void
    {
        $this->masukSebagai('admin');

        $jenisTes = JenisTes::where('nama', 'TOEFL iBT')->first();
        $jenisTesNonaktif = JenisTes::create([
            'kode' => '010',
            'nama' => 'Jenis Tes Nonaktif Untuk Bank',
            'batas_pelanggaran' => 3,
            'aktif' => false,
        ]);
        $bankSoal = BankSoal::create([
            'jenis_tes_id' => $jenisTes->id,
            'nama' => 'Bank Untuk Uji Perpindahan',
            'aktif' => true,
        ]);

        $this->put("/pustaka-soal/bank-soal/{$bankSoal->id}", [
            'jenis_tes_id' => $jenisTesNonaktif->id,
            'nama' => $bankSoal->nama,
            'aktif' => true,
        ])->assertSessionHasErrors('jenis_tes_id');

        $this->assertDatabaseHas('bank_soal', [
            'id' => $bankSoal->id,
            'jenis_tes_id' => $jenisTes->id,
        ]);
    }

    public function test_admin_dapat_menyimpan_soal_dengan_materi_tersedia(): void
    {
        $this->masukSebagai('admin');

        $materiSoal = $this->buatMateriTeks();
        $keterampilan = Keterampilan::where('nama', 'Reading')->first();

        $this->post('/pustaka-soal', [
            'bank_soal_id' => $this->bankSoal()->id,
            'materi_soal_id' => $materiSoal->id,
            'soal' => [
                [
                    'keterampilan_id' => $keterampilan->id,
                    'bagian_soal_id' => $this->bagianMembaca()->id,
                    'teks_soal' => 'What is the main topic?',
                    'pilihan_a' => 'A',
                    'pilihan_b' => 'B',
                    'pilihan_c' => 'C',
                    'pilihan_d' => 'D',
                    'kunci_jawaban' => 'A',
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('soal', [
            'teks_soal' => 'What is the main topic?',
            'keterampilan_id' => $keterampilan->id,
            'materi_soal_id' => $materiSoal->id,
        ]);
    }

    public function test_materi_soal_boleh_dipakai_beberapa_soal_dalam_group_yang_sama(): void
    {
        $this->masukSebagai('admin');

        $materiSoal = $this->buatMateriTeks();
        $keterampilan = Keterampilan::where('nama', 'Reading')->first();
        $bagianSoal = $this->bagianMembaca();

        foreach (['Soal pertama dalam group', 'Soal kedua dalam group'] as $teksSoal) {
            $this->post('/pustaka-soal', [
                'bank_soal_id' => $this->bankSoal()->id,
                'materi_soal_id' => $materiSoal->id,
                'soal' => [[
                    'keterampilan_id' => $keterampilan->id,
                    'bagian_soal_id' => $bagianSoal->id,
                    'teks_soal' => $teksSoal,
                    'pilihan_a' => 'A',
                    'pilihan_b' => 'B',
                    'pilihan_c' => 'C',
                    'pilihan_d' => 'D',
                    'kunci_jawaban' => 'A',
                ]],
            ])->assertRedirect();
        }

        $this->assertSame(2, Soal::where('materi_soal_id', $materiSoal->id)->count());
    }

    public function test_materi_soal_ditolak_pada_group_lain(): void
    {
        $this->masukSebagai('admin');

        $materiSoal = $this->buatMateriTeks();
        $keterampilan = Keterampilan::where('nama', 'Reading')->first();
        $bagianSoalLain = $this->bagianMembacaLain();

        $this->post('/pustaka-soal', [
            'bank_soal_id' => $this->bankSoal()->id,
            'materi_soal_id' => $materiSoal->id,
            'soal' => [[
                'keterampilan_id' => $keterampilan->id,
                'bagian_soal_id' => $this->bagianMembaca()->id,
                'teks_soal' => 'Soal group pertama',
                'pilihan_a' => 'A',
                'pilihan_b' => 'B',
                'pilihan_c' => 'C',
                'pilihan_d' => 'D',
                'kunci_jawaban' => 'A',
            ]],
        ])->assertRedirect();

        $response = $this->post('/pustaka-soal', [
            'bank_soal_id' => $this->bankSoal()->id,
            'materi_soal_id' => $materiSoal->id,
            'soal' => [[
                'keterampilan_id' => $keterampilan->id,
                'bagian_soal_id' => $bagianSoalLain->id,
                'teks_soal' => 'Soal group kedua',
                'pilihan_a' => 'A',
                'pilihan_b' => 'B',
                'pilihan_c' => 'C',
                'pilihan_d' => 'D',
                'kunci_jawaban' => 'A',
            ]],
        ]);

        $response->assertSessionHasErrors('materi_soal_id');

        $this->assertDatabaseMissing('soal', ['teks_soal' => 'Soal group kedua']);
    }

    public function test_jalur_tambah_soal_satuan_menolak_materi_dari_group_lain(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = $this->bankSoal();
        $materiSoal = $this->buatMateriTeks($bankSoal);
        $keterampilan = Keterampilan::where('nama', 'Reading')->first();
        $bagianSoalLain = $this->bagianMembacaLain();

        Soal::create([
            'bank_soal_id' => $bankSoal->id,
            'materi_soal_id' => $materiSoal->id,
            'keterampilan_id' => $keterampilan->id,
            'bagian_soal_id' => $this->bagianMembaca()->id,
            'teks_soal' => 'Soal existing group pertama',
            'pilihan_a' => 'A',
            'pilihan_b' => 'B',
            'pilihan_c' => 'C',
            'pilihan_d' => 'D',
            'kunci_jawaban' => 'A',
            'urutan' => 1,
        ]);

        $response = $this->post('/admin/ujian/soal', [
            'bank_soal_id' => $bankSoal->id,
            'materi_soal_id' => $materiSoal->id,
            'keterampilan_id' => $keterampilan->id,
            'bagian_soal_id' => $bagianSoalLain->id,
            'teks_soal' => 'Soal satuan group kedua',
            'pilihan_a' => 'A',
            'pilihan_b' => 'B',
            'pilihan_c' => 'C',
            'pilihan_d' => 'D',
            'kunci_jawaban' => 'A',
            'urutan' => 2,
        ]);

        $response->assertSessionHasErrors('materi_soal_id');

        $this->assertDatabaseMissing('soal', ['teks_soal' => 'Soal satuan group kedua']);
    }

    public function test_edit_soal_menolak_materi_jika_masih_dipakai_group_lain(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = $this->bankSoal();
        $materiSoal = $this->buatMateriTeks($bankSoal);
        $keterampilan = Keterampilan::where('nama', 'Reading')->first();
        $bagianMembaca = $this->bagianMembaca();
        $bagianSoalLain = $this->bagianMembacaLain();

        $soalPertama = Soal::create([
            'bank_soal_id' => $bankSoal->id,
            'materi_soal_id' => $materiSoal->id,
            'keterampilan_id' => $keterampilan->id,
            'bagian_soal_id' => $bagianMembaca->id,
            'teks_soal' => 'Soal edit pertama',
            'pilihan_a' => 'A',
            'pilihan_b' => 'B',
            'pilihan_c' => 'C',
            'pilihan_d' => 'D',
            'kunci_jawaban' => 'A',
            'urutan' => 1,
        ]);

        Soal::create([
            'bank_soal_id' => $bankSoal->id,
            'materi_soal_id' => $materiSoal->id,
            'keterampilan_id' => $keterampilan->id,
            'bagian_soal_id' => $bagianMembaca->id,
            'teks_soal' => 'Soal edit kedua',
            'pilihan_a' => 'A',
            'pilihan_b' => 'B',
            'pilihan_c' => 'C',
            'pilihan_d' => 'D',
            'kunci_jawaban' => 'A',
            'urutan' => 2,
        ]);

        $this->put("/pustaka-soal/soal/{$soalPertama->id}", [
            'bank_soal_id' => $bankSoal->id,
            'materi_soal_id' => $materiSoal->id,
            'keterampilan_id' => $keterampilan->id,
            'bagian_soal_id' => $bagianSoalLain->id,
            'teks_soal' => 'Soal edit pertama diperbarui',
            'pilihan_a' => 'A',
            'pilihan_b' => 'B',
            'pilihan_c' => 'C',
            'pilihan_d' => 'D',
            'kunci_jawaban' => 'A',
        ])->assertSessionHasErrors('materi_soal_id');

        $this->assertDatabaseHas('soal', [
            'id' => $soalPertama->id,
            'bagian_soal_id' => $bagianMembaca->id,
        ]);
    }

    public function test_edit_soal_tidak_dapat_mengganti_materi_soal(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = $this->bankSoal();
        $materiSoal = $this->buatMateriTeks($bankSoal);
        $materiSoalLain = $this->buatMateriTeks($bankSoal);
        $keterampilan = Keterampilan::where('nama', 'Reading')->first();
        $bagianSoal = $this->bagianMembaca();
        $soal = Soal::create([
            'bank_soal_id' => $bankSoal->id,
            'materi_soal_id' => $materiSoal->id,
            'keterampilan_id' => $keterampilan->id,
            'bagian_soal_id' => $bagianSoal->id,
            'teks_soal' => 'Soal yang tidak boleh pindah materi',
            'pilihan_a' => 'A',
            'pilihan_b' => 'B',
            'pilihan_c' => 'C',
            'pilihan_d' => 'D',
            'kunci_jawaban' => 'A',
            'urutan' => 1,
        ]);

        $this->put("/pustaka-soal/soal/{$soal->id}", [
            'bank_soal_id' => $bankSoal->id,
            'materi_soal_id' => $materiSoalLain->id,
            'keterampilan_id' => $keterampilan->id,
            'bagian_soal_id' => $bagianSoal->id,
            'teks_soal' => 'Percobaan mengganti materi',
            'pilihan_a' => 'A',
            'pilihan_b' => 'B',
            'pilihan_c' => 'C',
            'pilihan_d' => 'D',
            'kunci_jawaban' => 'A',
        ])->assertSessionHasErrors('materi_soal_id');

        $this->assertDatabaseHas('soal', [
            'id' => $soal->id,
            'materi_soal_id' => $materiSoal->id,
            'teks_soal' => 'Soal yang tidak boleh pindah materi',
        ]);
    }

    public function test_endpoint_import_soal_json_dan_excel_sudah_dihapus(): void
    {
        $this->masukSebagai('admin');

        $this->post('/admin/ujian/soal/massal')->assertNotFound();
        $this->post('/admin/ujian/soal/impor')->assertNotFound();
    }

    public function test_soal_menyimak_mewajibkan_materi_audio(): void
    {
        $this->masukSebagai('admin');

        $materiTeks = $this->buatMateriTeks();
        $keterampilan = Keterampilan::where('nama', 'Listening')->first();

        $this->post('/pustaka-soal', [
            'bank_soal_id' => $this->bankSoal()->id,
            'materi_soal_id' => $materiTeks->id,
            'soal' => [
                [
                    'keterampilan_id' => $keterampilan->id,
                    'bagian_soal_id' => $this->bagianMenyimak()->id,
                    'teks_soal' => null,
                    'pilihan_a' => null,
                    'pilihan_b' => null,
                    'pilihan_c' => null,
                    'pilihan_d' => null,
                    'kunci_jawaban' => 'A',
                ],
            ],
        ])->assertSessionHasErrors('soal');

        $this->assertDatabaseMissing('soal', [
            'materi_soal_id' => $materiTeks->id,
            'keterampilan_id' => $keterampilan->id,
        ]);
    }

    public function test_soal_menyimak_menerima_materi_audio(): void
    {
        $this->masukSebagai('admin');

        $materiAudio = $this->buatMateriAudio();
        $keterampilan = Keterampilan::where('nama', 'Listening')->first();

        $this->post('/pustaka-soal', [
            'bank_soal_id' => $this->bankSoal()->id,
            'materi_soal_id' => $materiAudio->id,
            'soal' => [
                [
                    'keterampilan_id' => $keterampilan->id,
                    'bagian_soal_id' => $this->bagianMenyimak()->id,
                    'teks_soal' => null,
                    'pilihan_a' => null,
                    'pilihan_b' => null,
                    'pilihan_c' => null,
                    'pilihan_d' => null,
                    'kunci_jawaban' => 'A',
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('soal', [
            'materi_soal_id' => $materiAudio->id,
            'keterampilan_id' => $keterampilan->id,
        ]);
    }

    public function test_jalur_tambah_soal_satuan_mewajibkan_integritas_soal_teks(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = $this->bankSoal();
        $keterampilan = Keterampilan::where('nama', 'Reading')->firstOrFail();
        $bagianSoal = $this->bagianMembaca();

        $this->post('/admin/ujian/soal', [
            'bank_soal_id' => $bankSoal->id,
            'keterampilan_id' => $keterampilan->id,
            'bagian_soal_id' => $bagianSoal->id,
            'teks_soal' => null,
            'pilihan_a' => null,
            'pilihan_b' => null,
            'pilihan_c' => null,
            'pilihan_d' => null,
            'kunci_jawaban' => 'A',
            'urutan' => 1,
        ])->assertSessionHasErrors('teks_soal');

        $this->assertDatabaseMissing('soal', [
            'bank_soal_id' => $bankSoal->id,
            'bagian_soal_id' => $bagianSoal->id,
        ]);
    }

    public function test_jalur_tambah_soal_satuan_menolak_keterampilan_dari_jenis_tes_lain(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = $this->bankSoal();
        $jenisTesLain = JenisTes::create([
            'kode' => '010',
            'nama' => 'Jenis Tes Relasi Soal',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);
        $keterampilanLain = Keterampilan::create([
            'jenis_tes_id' => $jenisTesLain->id,
            'kode' => '010001',
            'nama' => 'Reading',
            'urutan' => 1,
            'aktif' => true,
        ]);

        $this->post('/admin/ujian/soal', [
            'bank_soal_id' => $bankSoal->id,
            'keterampilan_id' => $keterampilanLain->id,
            'bagian_soal_id' => $this->bagianMembaca()->id,
            'teks_soal' => 'Soal lintas jenis tes',
            'pilihan_a' => 'A',
            'pilihan_b' => 'B',
            'pilihan_c' => 'C',
            'pilihan_d' => 'D',
            'kunci_jawaban' => 'A',
            'urutan' => 1,
        ])->assertSessionHasErrors('keterampilan_id');

        $this->assertDatabaseMissing('soal', ['teks_soal' => 'Soal lintas jenis tes']);
    }

    public function test_materi_dari_bank_lain_ditolak(): void
    {
        $this->masukSebagai('admin');

        $bankSoalLain = BankSoal::create([
            'nama' => 'Bank Soal Lain',
            'jenis_tes_id' => JenisTes::first()->id,
            'aktif' => true,
        ]);

        $materiBankLain = $this->buatMateriTeks($bankSoalLain);
        $keterampilan = Keterampilan::where('nama', 'Reading')->first();

        $this->post('/pustaka-soal', [
            'bank_soal_id' => $this->bankSoal()->id,
            'materi_soal_id' => $materiBankLain->id,
            'soal' => [
                [
                    'keterampilan_id' => $keterampilan->id,
                    'bagian_soal_id' => $this->bagianMembaca()->id,
                    'teks_soal' => 'Soal memakai passage bank lain',
                    'pilihan_a' => 'A',
                    'pilihan_b' => 'B',
                    'pilihan_c' => 'C',
                    'pilihan_d' => 'D',
                    'kunci_jawaban' => 'A',
                ],
            ],
        ])->assertSessionHasErrors('materi_soal_id');

        $this->assertDatabaseMissing('soal', [
            'teks_soal' => 'Soal memakai passage bank lain',
        ]);
    }

    public function test_update_materi_soal_menolak_materi_kosong(): void
    {
        $this->masukSebagai('admin');

        $materiSoal = $this->buatMateriTeks();

        $this->put("/pustaka-soal/materi-soal/{$materiSoal->id}", [
            'judul' => $materiSoal->judul,
            'isi_teks' => null,
        ])->assertSessionHasErrors('materi_soal');

        $this->assertDatabaseHas('materi_soal', [
            'id' => $materiSoal->id,
            'isi_teks' => '<p>Isi passage teks.</p>',
        ]);
    }

    public function test_media_materi_soal_dapat_dihapus_jika_materi_tetap_valid(): void
    {
        $this->masukSebagai('admin');

        $materiSoal = $this->buatMateriTeks();
        $materiSoal->mediaMateriSoal()->create([
            'jenis_media' => 'audio',
            'lokasi_berkas' => 'passages/audio/audio-hapus.mp3',
            'urutan' => 0,
        ]);

        $this->delete("/pustaka-soal/materi-soal/{$materiSoal->id}/media/audio")
            ->assertRedirect();

        $this->assertDatabaseMissing('media_materi_soal', [
            'materi_soal_id' => $materiSoal->id,
            'jenis_media' => 'audio',
        ]);
    }

    public function test_media_wajib_tidak_dapat_dihapus_jika_materi_menjadi_kosong(): void
    {
        $this->masukSebagai('admin');

        $materiSoal = $this->buatMateriAudio();

        $this->delete("/pustaka-soal/materi-soal/{$materiSoal->id}/media/audio")
            ->assertSessionHasErrors('materi_soal');

        $this->assertDatabaseHas('media_materi_soal', [
            'materi_soal_id' => $materiSoal->id,
            'jenis_media' => 'audio',
        ]);
    }

    public function test_soal_membaca_menolak_materi_audio(): void
    {
        $this->masukSebagai('admin');

        $materiAudio = $this->buatMateriAudio();
        $keterampilan = Keterampilan::where('nama', 'Reading')->first();

        $this->post('/pustaka-soal', [
            'bank_soal_id' => $this->bankSoal()->id,
            'materi_soal_id' => $materiAudio->id,
            'soal' => [
                [
                    'keterampilan_id' => $keterampilan->id,
                    'bagian_soal_id' => $this->bagianMembaca()->id,
                    'teks_soal' => 'Soal reading memakai passage audio',
                    'pilihan_a' => 'A',
                    'pilihan_b' => 'B',
                    'pilihan_c' => 'C',
                    'pilihan_d' => 'D',
                    'kunci_jawaban' => 'A',
                ],
            ],
        ])->assertSessionHasErrors('soal');

        $this->assertDatabaseMissing('soal', [
            'teks_soal' => 'Soal reading memakai passage audio',
        ]);
    }

    public function test_keterampilan_tanpa_kebijakan_ditolak(): void
    {
        $this->masukSebagai('admin');

        $berbicara = Keterampilan::create([
            'jenis_tes_id' => $this->bankSoal()->jenis_tes_id,
            'kode' => '001003',
            'nama' => 'Speaking',
            'urutan' => 3,
            'aktif' => true,
        ]);

        $bagianSoal = BagianSoal::create([
            'bank_soal_id' => $this->bankSoal()->id,
            'keterampilan_id' => $berbicara->id,
            'nama' => 'Part 1',
            'urutan' => 1,
            'aktif' => true,
        ]);

        $this->post('/pustaka-soal', [
            'bank_soal_id' => $this->bankSoal()->id,
            'soal' => [
                [
                    'keterampilan_id' => $berbicara->id,
                    'bagian_soal_id' => $bagianSoal->id,
                    'teks_soal' => 'Soal skill tanpa policy',
                    'pilihan_a' => 'A',
                    'pilihan_b' => 'B',
                    'pilihan_c' => 'C',
                    'pilihan_d' => 'D',
                    'kunci_jawaban' => 'A',
                ],
            ],
        ])->assertSessionHasErrors('soal');

        $this->assertDatabaseMissing('soal', [
            'teks_soal' => 'Soal skill tanpa policy',
        ]);
    }

    public function test_keterampilan_dinamis_dengan_kebijakan_audio_menerima_soal_menyimak(): void
    {
        $this->masukSebagai('admin');

        $berbicara = Keterampilan::create([
            'jenis_tes_id' => $this->bankSoal()->jenis_tes_id,
            'kode' => '001003',
            'nama' => 'Speaking Dinamis',
            'urutan' => 3,
            'aktif' => true,
        ]);

        $bagianSoal = BagianSoal::create([
            'bank_soal_id' => $this->bankSoal()->id,
            'keterampilan_id' => $berbicara->id,
            'nama' => 'Part 1',
            'urutan' => 1,
            'aktif' => true,
        ]);

        // Policy dinamis: audio wajib — dibuat lewat data, bukan kode hardcoded.
        $kebijakan = KebijakanMateriSoal::create([
            'keterampilan_id' => $berbicara->id,
            'ketentuan_materi' => 'required',
        ]);
        $kebijakan->aturanMedia()->create(['jenis_media' => 'audio', 'ketentuan' => 'required']);
        $kebijakan->aturanMedia()->create(['jenis_media' => 'text', 'ketentuan' => 'forbidden']);
        $kebijakan->aturanMedia()->create(['jenis_media' => 'image', 'ketentuan' => 'optional']);

        $materiAudio = $this->buatMateriAudio();

        $this->post('/pustaka-soal', [
            'bank_soal_id' => $this->bankSoal()->id,
            'materi_soal_id' => $materiAudio->id,
            'soal' => [
                [
                    'keterampilan_id' => $berbicara->id,
                    'bagian_soal_id' => $bagianSoal->id,
                    'teks_soal' => null,
                    'pilihan_a' => null,
                    'pilihan_b' => null,
                    'pilihan_c' => null,
                    'pilihan_d' => null,
                    'kunci_jawaban' => 'A',
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('soal', [
            'materi_soal_id' => $materiAudio->id,
            'keterampilan_id' => $berbicara->id,
        ]);
    }

    public function test_soal_tidak_dapat_memakai_bagian_dari_bank_lain(): void
    {
        $this->masukSebagai('admin');

        $bankSoalLain = BankSoal::create([
            'nama' => 'Bank Soal Lain',
            'jenis_tes_id' => JenisTes::first()->id,
            'aktif' => true,
        ]);

        $keterampilan = Keterampilan::where('nama', 'Reading')->first();

        $bagianBankLain = BagianSoal::create([
            'bank_soal_id' => $bankSoalLain->id,
            'keterampilan_id' => $keterampilan->id,
            'nama' => 'Part 1',
            'urutan' => 1,
            'aktif' => true,
        ]);

        $this->post('/pustaka-soal', [
            'bank_soal_id' => $this->bankSoal()->id,
            'soal' => [
                [
                    'keterampilan_id' => $keterampilan->id,
                    'bagian_soal_id' => $bagianBankLain->id,
                    'teks_soal' => 'Soal memakai part bank lain',
                    'pilihan_a' => 'A',
                    'pilihan_b' => 'B',
                    'pilihan_c' => 'C',
                    'pilihan_d' => 'D',
                    'kunci_jawaban' => 'A',
                ],
            ],
        ])->assertSessionHasErrors('soal');

        $this->assertDatabaseMissing('soal', [
            'teks_soal' => 'Soal memakai part bank lain',
        ]);
    }

    public function test_soal_membaca_mewajibkan_semua_pilihan(): void
    {
        $this->masukSebagai('admin');

        $keterampilan = Keterampilan::where('nama', 'Reading')->first();

        $this->post('/pustaka-soal', [
            'bank_soal_id' => $this->bankSoal()->id,
            'soal' => [
                [
                    'keterampilan_id' => $keterampilan->id,
                    'bagian_soal_id' => $this->bagianMembaca()->id,
                    'teks_soal' => 'Pertanyaan tanpa opsi lengkap',
                    'pilihan_a' => 'A',
                    'pilihan_b' => 'B',
                    'pilihan_c' => 'C',
                    'pilihan_d' => '',
                    'kunci_jawaban' => 'A',
                ],
            ],
        ])->assertSessionHasErrors('soal');

        $this->assertDatabaseCount('soal', 8);
    }

    public function test_media_materi_ditampilkan_sebagai_atribut(): void
    {
        $materiSoal = $this->buatMateriAudio();

        $this->assertTrue($materiSoal->memiliki_audio);
        $this->assertFalse($materiSoal->memiliki_gambar);
        $this->assertSame('passages/audio/test-audio.mp3', $materiSoal->url_audio);
        $this->assertNull($materiSoal->url_gambar);

        $serialized = $materiSoal->toArray();

        $this->assertArrayHasKey('memiliki_audio', $serialized);
        $this->assertArrayHasKey('url_audio', $serialized);
    }

    public function test_tabel_media_materi_menyimpan_referensi_unggahan(): void
    {
        $materiSoal = $this->buatMateriAudio();

        $this->assertDatabaseHas('media_materi_soal', [
            'materi_soal_id' => $materiSoal->id,
            'jenis_media' => 'audio',
            'lokasi_berkas' => 'passages/audio/test-audio.mp3',
        ]);
    }
}
