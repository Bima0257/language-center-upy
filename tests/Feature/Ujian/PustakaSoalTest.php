<?php

namespace Tests\Feature\Ujian;

use App\Models\BankSoal;
use App\Models\KebijakanMateriSoal;
use App\Models\MateriSoal;
use App\Models\PartSoal;
use App\Models\Pengguna;
use App\Models\ProgramTes;
use App\Models\Section;
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
        return BankSoal::firstOrCreate(['nama' => 'Bank Soal Latihan'], [
            'program_tes_id' => ProgramTes::first()->id,
            'aktif' => true,
        ]);
    }

    private function bankSoalTerkunci(): BankSoal
    {
        return BankSoal::where('nama', 'Bank Soal EPT')->firstOrFail();
    }

    private function partMembaca(): PartSoal
    {
        $section = Section::where('nama', 'Reading')->first();

        return PartSoal::where('bank_soal_id', $this->bankSoal()->id)
            ->where('section_id', $section->id)
            ->orderBy('urutan')
            ->first();
    }

    private function partMenyimak(): PartSoal
    {
        $section = Section::where('nama', 'Listening')->first();

        return PartSoal::where('bank_soal_id', $this->bankSoal()->id)
            ->where('section_id', $section->id)
            ->first();
    }

    private function partMembacaLain(): PartSoal
    {
        $partMembaca = $this->partMembaca();

        return PartSoal::create([
            'bank_soal_id' => $partMembaca->bank_soal_id,
            'section_id' => $partMembaca->section_id,
            'nama' => 'Part Membaca Alternatif',
            'urutan' => $partMembaca->urutan + 1,
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
        $this->masukSebagai('peserta');

        $this->get('/pustaka-soal')->assertForbidden();
    }

    public function test_admin_dapat_mengakses_pustaka_soal(): void
    {
        $this->masukSebagai('admin');

        $this->get('/pustaka-soal')->assertOk();
    }

    public function test_bank_yang_dipakai_ujian_ditandai_terkunci_di_pustaka(): void
    {
        $this->masukSebagai('admin');
        $bankSoal = $this->bankSoalTerkunci();

        $halaman = $this->get('/pustaka-soal?bank_soal_id='.$bankSoal->id)
            ->assertOk()
            ->inertiaPage();

        $this->assertTrue($halaman['props']['bankTerkunci']);
    }

    public function test_section_tidak_dapat_diubah_dari_pustaka_bank_terkunci(): void
    {
        $this->masukSebagai('admin');
        $bankSoal = $this->bankSoalTerkunci();
        $section = Section::where('program_tes_id', $bankSoal->program_tes_id)
            ->where('nama', 'Reading')
            ->firstOrFail();

        $this->put(route('admin.data-induk.section.perbarui', $section), [
            'bank_soal_konteks_id' => $bankSoal->id,
            'nama' => 'Reading Terkunci',
            'deskripsi' => $section->deskripsi,
            'aktif' => $section->aktif,
        ])->assertSessionHasErrors('bank_soal_konteks_id');

        $this->assertDatabaseHas('section', [
            'id' => $section->id,
            'nama' => 'Reading',
        ]);
    }

    public function test_section_baru_tidak_dapat_dibuat_dari_pustaka_bank_terkunci(): void
    {
        $this->masukSebagai('admin');
        $bankSoal = $this->bankSoalTerkunci();

        $this->post(route('admin.data-induk.section.simpan'), [
            'bank_soal_konteks_id' => $bankSoal->id,
            'program_tes_id' => $bankSoal->program_tes_id,
            'nama' => 'Section Terkunci Baru',
            'aktif' => true,
        ])->assertSessionHasErrors('bank_soal_konteks_id');

        $this->assertDatabaseMissing('section', ['nama' => 'Section Terkunci Baru']);
    }

    public function test_penghapusan_section_dari_pustaka_bank_terkunci_ditolak(): void
    {
        $this->masukSebagai('admin');
        $bankSoal = $this->bankSoalTerkunci();
        $section = Section::where('program_tes_id', $bankSoal->program_tes_id)
            ->where('nama', 'Reading')
            ->firstOrFail();

        $this->delete(route('admin.data-induk.section.hapus', $section), [
            'bank_soal_konteks_id' => $bankSoal->id,
        ])->assertSessionHasErrors('bank_soal_konteks_id');

        $this->assertDatabaseHas('section', ['id' => $section->id]);
    }

    public function test_part_soal_tidak_dapat_diubah_dari_pustaka_bank_terkunci(): void
    {
        $this->masukSebagai('admin');
        $bankSoal = $this->bankSoalTerkunci();
        $partSoal = PartSoal::where('bank_soal_id', $bankSoal->id)->firstOrFail();

        $this->put(route('admin.data-induk.part-soal.perbarui', $partSoal), [
            'bank_soal_konteks_id' => $bankSoal->id,
            'bank_soal_id' => $partSoal->bank_soal_id,
            'section_id' => $partSoal->section_id,
            'nama' => 'Part Terkunci',
            'petunjuk' => $partSoal->petunjuk,
            'aktif' => $partSoal->aktif,
        ])->assertSessionHasErrors('bank_soal_konteks_id');

        $this->assertDatabaseHas('part_soal', [
            'id' => $partSoal->id,
            'nama' => $partSoal->nama,
        ]);
    }

    public function test_part_soal_baru_tidak_dapat_dibuat_dari_pustaka_bank_terkunci(): void
    {
        $this->masukSebagai('admin');
        $bankSoal = $this->bankSoalTerkunci();
        $partSoal = PartSoal::where('bank_soal_id', $bankSoal->id)->firstOrFail();

        $this->post(route('admin.data-induk.part-soal.simpan'), [
            'bank_soal_konteks_id' => $bankSoal->id,
            'bank_soal_id' => $bankSoal->id,
            'section_id' => $partSoal->section_id,
            'nama' => 'Part Terkunci Baru',
            'aktif' => true,
        ])->assertSessionHasErrors('bank_soal_konteks_id');

        $this->assertDatabaseMissing('part_soal', ['nama' => 'Part Terkunci Baru']);
    }

    public function test_penghapusan_part_soal_dari_pustaka_bank_terkunci_ditolak(): void
    {
        $this->masukSebagai('admin');
        $bankSoal = $this->bankSoalTerkunci();
        $partSoal = PartSoal::where('bank_soal_id', $bankSoal->id)->firstOrFail();

        $this->delete(route('admin.data-induk.part-soal.hapus', $partSoal), [
            'bank_soal_konteks_id' => $bankSoal->id,
        ])->assertSessionHasErrors('bank_soal_konteks_id');

        $this->assertDatabaseHas('part_soal', ['id' => $partSoal->id]);
    }

    public function test_pustaka_soal_memilih_section_dan_part_pertama_secara_default(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal Latihan')->firstOrFail();
        $sectionPertama = Section::where('program_tes_id', $bankSoal->program_tes_id)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->firstOrFail();
        $partSoalPertama = PartSoal::where('bank_soal_id', $bankSoal->id)
            ->where('section_id', $sectionPertama->id)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->firstOrFail();

        $page = $this->get('/pustaka-soal?bank_soal_id='.$bankSoal->id.'&program_tes_id='.$bankSoal->program_tes_id)
            ->assertOk()
            ->inertiaPage();

        $this->assertSame($bankSoal->id, $page['props']['bankSoalTerpilih']['id']);
        $this->assertSame($bankSoal->nama, $page['props']['bankSoalTerpilih']['nama']);
        $this->assertSame($sectionPertama->id, $page['props']['filter']['section']);
        $this->assertSame($partSoalPertama->id, $page['props']['filter']['part_soal_id']);
        $this->assertTrue(collect($page['props']['soal']['data'])->every(
            fn (array $soal): bool => $soal['section_id'] === $sectionPertama->id
                && $soal['part_soal_id'] === $partSoalPertama->id,
        ));

        $sectionLain = Section::where('program_tes_id', $bankSoal->program_tes_id)
            ->where('aktif', true)
            ->where('id', '!=', $sectionPertama->id)
            ->orderBy('urutan')
            ->firstOrFail();
        $partSoalLain = PartSoal::where('bank_soal_id', $bankSoal->id)
            ->where('section_id', $sectionLain->id)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->firstOrFail();
        $filteredPage = $this->get(
            '/pustaka-soal?bank_soal_id='.$bankSoal->id
                .'&program_tes_id='.$bankSoal->program_tes_id
                .'&section='.$sectionLain->id
                .'&part_soal_id='.$partSoalLain->id,
        )->assertOk()->inertiaPage();

        $this->assertSame($sectionLain->id, $filteredPage['props']['filter']['section']);
        $this->assertSame($partSoalLain->id, $filteredPage['props']['filter']['part_soal_id']);
    }

    public function test_pustaka_soal_menentukan_bank_dari_filter_part_soal(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal Latihan')->firstOrFail();
        $section = Section::where('program_tes_id', $bankSoal->program_tes_id)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->firstOrFail();
        $partSoal = PartSoal::where('bank_soal_id', $bankSoal->id)
            ->where('section_id', $section->id)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->firstOrFail();

        $page = $this->get('/pustaka-soal?part_soal_id='.$partSoal->id)
            ->assertOk()
            ->inertiaPage();

        $this->assertSame($partSoal->bank_soal_id, $page['props']['bankSoalTerpilihId']);
        $this->assertSame($partSoal->bank_soal_id, $page['props']['filter']['bank_soal_id']);
        $this->assertSame($partSoal->section_id, $page['props']['filter']['section']);
        $this->assertSame($partSoal->id, $page['props']['filter']['part_soal_id']);
    }

    public function test_pustaka_soal_menampilkan_seluruh_soal_satu_part_tanpa_terpotong_pagination(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = $this->bankSoal();
        $section = Section::where('nama', 'Reading')->first();
        $partSoal = $this->partMembaca();
        $materiSoal = $this->buatMateriTeks($bankSoal);

        $idSoalDibuat = [];

        for ($urutan = 1; $urutan <= 25; $urutan++) {
            $soal = Soal::create([
                'bank_soal_id' => $bankSoal->id,
                'materi_soal_id' => $materiSoal->id,
                'section_id' => $section->id,
                'part_soal_id' => $partSoal->id,
                'teks_soal' => "Soal regresi pagination {$urutan}",
                'pilihan_a' => 'A',
                'pilihan_b' => 'B',
                'pilihan_c' => 'C',
                'pilihan_d' => 'D',
                'kunci_jawaban' => 'A',
                'urutan' => $urutan,
            ]);

            $idSoalDibuat[] = $soal->id;
        }

        $page = $this->get(
            '/pustaka-soal?bank_soal_id='.$bankSoal->id
                .'&program_tes_id='.$bankSoal->program_tes_id
                .'&section='.$section->id
                .'&part_soal_id='.$partSoal->id,
        )->assertOk()->inertiaPage();

        $idSoalTampil = collect($page['props']['soal']['data'])->pluck('id');

        $this->assertCount(25, $idSoalTampil);
        $this->assertTrue($idSoalTampil->every(
            fn (string $id): bool => in_array($id, $idSoalDibuat, true),
        ));
    }

    public function test_pustaka_soal_fallback_ke_section_aktif_jika_filter_tidak_valid(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal Latihan')->firstOrFail();
        $sectionNonaktif = Section::where('program_tes_id', $bankSoal->program_tes_id)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->firstOrFail();
        $sectionNonaktif->update(['aktif' => false]);

        $sectionAktif = Section::where('program_tes_id', $bankSoal->program_tes_id)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->firstOrFail();

        $page = $this->get(
            '/pustaka-soal?bank_soal_id='.$bankSoal->id
                .'&program_tes_id='.$bankSoal->program_tes_id
                .'&section='.$sectionNonaktif->id,
        )->assertOk()->inertiaPage();

        $this->assertSame($sectionAktif->id, $page['props']['filter']['section']);
    }

    public function test_bulk_create_membawa_filter_section_dan_part_soal_dari_pustaka(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal Latihan')->firstOrFail();
        $section = Section::where('program_tes_id', $bankSoal->program_tes_id)
            ->where('nama', 'Reading')
            ->where('aktif', true)
            ->firstOrFail();
        $partSoal = PartSoal::where('bank_soal_id', $bankSoal->id)
            ->where('section_id', $section->id)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->firstOrFail();

        $page = $this->get(
            '/pustaka-soal/tambah?bank_soal_id='.$bankSoal->id
                .'&section='.$section->id
                .'&part_soal_id='.$partSoal->id,
        )->assertOk()->inertiaPage();

        $this->assertSame($bankSoal->id, $page['props']['bankSoalTerpilih']);
        $this->assertSame($section->id, $page['props']['filterKembali']['section']);
        $this->assertSame($partSoal->id, $page['props']['filterKembali']['part_soal_id']);
        $this->assertArrayHasKey('materiSoal', $page['props']);
        $this->assertTrue(collect($page['props']['materiSoal'])->every(
            fn (array $materi): bool => $materi['bank_soal_id'] === $bankSoal->id,
        ));
        $this->assertArrayNotHasKey('grupMateriSoal', $page['props']);
    }

    public function test_admin_dapat_menavigasi_hierarki_program_tes_dan_bank_soal(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal Latihan')->firstOrFail();
        $programTes = $bankSoal->programTes;
        $programTesLain = ProgramTes::create([
            'kode' => '010',
            'nama' => 'Program Tes Navigasi Lain',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);
        $bankSoalLain = BankSoal::create([
            'program_tes_id' => $programTesLain->id,
            'nama' => 'Bank Soal Navigasi Lain',
            'aktif' => true,
        ]);

        $root = $this->get('/pustaka-soal')
            ->assertOk()
            ->inertiaPage();
        $bank = $this->get('/pustaka-soal?program_tes_id='.$programTes->id)
            ->assertOk()
            ->inertiaPage();
        $detail = $this->get('/pustaka-soal?program_tes_id='.$programTes->id.'&bank_soal_id='.$bankSoal->id)
            ->assertOk()
            ->inertiaPage();

        $rootBank = collect($root['props']['bankSoal'])->firstWhere('id', $bankSoal->id);
        $bankIds = collect($bank['props']['bankSoal'])->pluck('id');

        $this->assertNull($root['props']['programTesTerpilihId']);
        $this->assertNull($root['props']['bankSoalTerpilihId']);
        $this->assertNotNull($rootBank);
        $this->assertSame($bankSoal->soal()->count(), (int) $rootBank['soal_count']);
        $this->assertSame($programTes->id, $bank['props']['programTesTerpilihId']);
        $this->assertTrue($bankIds->contains($bankSoal->id));
        $this->assertFalse($bankIds->contains($bankSoalLain->id));
        $this->assertSame($programTes->id, $detail['props']['programTesTerpilihId']);
        $this->assertSame($bankSoal->id, $detail['props']['bankSoalTerpilihId']);
    }

    public function test_admin_dapat_melihat_bank_soal_hanya_dari_program_tes_terpilih(): void
    {
        $this->masukSebagai('admin');

        $programTes = ProgramTes::create([
            'kode' => '010',
            'nama' => 'Program Tes Bank Soal Detail',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);
        $programTesLain = ProgramTes::create([
            'kode' => '011',
            'nama' => 'Program Tes Bank Soal Lain',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);
        $bankSoalTerpilih = BankSoal::create([
            'program_tes_id' => $programTes->id,
            'nama' => 'Bank Soal Dalam Program Tes',
            'aktif' => true,
        ]);
        $bankSoalLain = BankSoal::create([
            'program_tes_id' => $programTesLain->id,
            'nama' => 'Bank Soal Di Luar Program Tes',
            'aktif' => true,
        ]);
        Section::create([
            'program_tes_id' => $programTes->id,
            'kode' => '010001',
            'nama' => 'Section Aktif Bank Soal',
            'urutan' => 1,
            'aktif' => true,
        ]);
        Section::create([
            'program_tes_id' => $programTes->id,
            'kode' => '010002',
            'nama' => 'Section Nonaktif Bank Soal',
            'urutan' => 2,
            'aktif' => false,
        ]);

        $page = $this->get("/pustaka-soal/bank-soal/program-tes/{$programTes->id}")
            ->assertOk()
            ->inertiaPage();

        $bankSoalIds = collect($page['props']['bankSoal'])->pluck('id');
        $bankSoalPayload = collect($page['props']['bankSoal'])->firstWhere('id', $bankSoalTerpilih->id);

        $this->assertSame('BankSoal/KelolaBankSoal/index', $page['component']);
        $this->assertSame($programTes->id, $page['props']['programTesTerpilih']['id']);
        $this->assertTrue($bankSoalIds->contains($bankSoalTerpilih->id));
        $this->assertFalse($bankSoalIds->contains($bankSoalLain->id));
        $this->assertNotNull($bankSoalPayload);
        $this->assertSame(1, (int) $bankSoalPayload['program_tes']['section_aktif_count']);
    }

    public function test_bank_soal_tidak_dapat_dipindah_ke_program_tes_nonaktif(): void
    {
        $this->masukSebagai('admin');

        $programTes = ProgramTes::where('nama', 'EPT')->first();
        $programTesNonaktif = ProgramTes::create([
            'kode' => '010',
            'nama' => 'Program Tes Nonaktif Untuk Bank',
            'batas_pelanggaran' => 3,
            'aktif' => false,
        ]);
        $bankSoal = BankSoal::create([
            'program_tes_id' => $programTes->id,
            'nama' => 'Bank Untuk Uji Perpindahan',
            'aktif' => true,
        ]);

        $this->put("/pustaka-soal/bank-soal/{$bankSoal->id}", [
            'program_tes_id' => $programTesNonaktif->id,
            'nama' => $bankSoal->nama,
            'aktif' => true,
        ])->assertSessionHasErrors('program_tes_id');

        $this->assertDatabaseHas('bank_soal', [
            'id' => $bankSoal->id,
            'program_tes_id' => $programTes->id,
        ]);
    }

    public function test_admin_dapat_menyimpan_soal_dengan_materi_tersedia(): void
    {
        $this->masukSebagai('admin');

        $materiSoal = $this->buatMateriTeks();
        $section = Section::where('nama', 'Reading')->first();

        $this->post('/pustaka-soal', [
            'bank_soal_id' => $this->bankSoal()->id,
            'materi_soal_id' => $materiSoal->id,
            'soal' => [
                [
                    'section_id' => $section->id,
                    'part_soal_id' => $this->partMembaca()->id,
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
            'section_id' => $section->id,
            'materi_soal_id' => $materiSoal->id,
        ]);
    }

    public function test_materi_soal_boleh_dipakai_beberapa_soal_dalam_group_yang_sama(): void
    {
        $this->masukSebagai('admin');

        $materiSoal = $this->buatMateriTeks();
        $section = Section::where('nama', 'Reading')->first();
        $partSoal = $this->partMembaca();

        foreach (['Soal pertama dalam group', 'Soal kedua dalam group'] as $teksSoal) {
            $this->post('/pustaka-soal', [
                'bank_soal_id' => $this->bankSoal()->id,
                'materi_soal_id' => $materiSoal->id,
                'soal' => [[
                    'section_id' => $section->id,
                    'part_soal_id' => $partSoal->id,
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
        $section = Section::where('nama', 'Reading')->first();
        $partSoalLain = $this->partMembacaLain();

        $this->post('/pustaka-soal', [
            'bank_soal_id' => $this->bankSoal()->id,
            'materi_soal_id' => $materiSoal->id,
            'soal' => [[
                'section_id' => $section->id,
                'part_soal_id' => $this->partMembaca()->id,
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
                'section_id' => $section->id,
                'part_soal_id' => $partSoalLain->id,
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
        $section = Section::where('nama', 'Reading')->first();
        $partSoalLain = $this->partMembacaLain();

        Soal::create([
            'bank_soal_id' => $bankSoal->id,
            'materi_soal_id' => $materiSoal->id,
            'section_id' => $section->id,
            'part_soal_id' => $this->partMembaca()->id,
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
            'section_id' => $section->id,
            'part_soal_id' => $partSoalLain->id,
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
        $section = Section::where('nama', 'Reading')->first();
        $partMembaca = $this->partMembaca();
        $partSoalLain = $this->partMembacaLain();

        $soalPertama = Soal::create([
            'bank_soal_id' => $bankSoal->id,
            'materi_soal_id' => $materiSoal->id,
            'section_id' => $section->id,
            'part_soal_id' => $partMembaca->id,
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
            'section_id' => $section->id,
            'part_soal_id' => $partMembaca->id,
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
            'section_id' => $section->id,
            'part_soal_id' => $partSoalLain->id,
            'teks_soal' => 'Soal edit pertama diperbarui',
            'pilihan_a' => 'A',
            'pilihan_b' => 'B',
            'pilihan_c' => 'C',
            'pilihan_d' => 'D',
            'kunci_jawaban' => 'A',
        ])->assertSessionHasErrors('materi_soal_id');

        $this->assertDatabaseHas('soal', [
            'id' => $soalPertama->id,
            'part_soal_id' => $partMembaca->id,
        ]);
    }

    public function test_edit_soal_tidak_dapat_mengganti_materi_soal(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = $this->bankSoal();
        $materiSoal = $this->buatMateriTeks($bankSoal);
        $materiSoalLain = $this->buatMateriTeks($bankSoal);
        $section = Section::where('nama', 'Reading')->first();
        $partSoal = $this->partMembaca();
        $soal = Soal::create([
            'bank_soal_id' => $bankSoal->id,
            'materi_soal_id' => $materiSoal->id,
            'section_id' => $section->id,
            'part_soal_id' => $partSoal->id,
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
            'section_id' => $section->id,
            'part_soal_id' => $partSoal->id,
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
        $section = Section::where('nama', 'Listening')->first();

        $this->post('/pustaka-soal', [
            'bank_soal_id' => $this->bankSoal()->id,
            'materi_soal_id' => $materiTeks->id,
            'soal' => [
                [
                    'section_id' => $section->id,
                    'part_soal_id' => $this->partMenyimak()->id,
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
            'section_id' => $section->id,
        ]);
    }

    public function test_soal_menyimak_menerima_materi_audio(): void
    {
        $this->masukSebagai('admin');

        $materiAudio = $this->buatMateriAudio();
        $section = Section::where('nama', 'Listening')->first();

        $this->post('/pustaka-soal', [
            'bank_soal_id' => $this->bankSoal()->id,
            'materi_soal_id' => $materiAudio->id,
            'soal' => [
                [
                    'section_id' => $section->id,
                    'part_soal_id' => $this->partMenyimak()->id,
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
            'section_id' => $section->id,
        ]);
    }

    public function test_jalur_tambah_soal_satuan_mewajibkan_integritas_soal_teks(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = $this->bankSoal();
        $section = Section::where('nama', 'Reading')->firstOrFail();
        $partSoal = $this->partMembaca();

        $this->post('/admin/ujian/soal', [
            'bank_soal_id' => $bankSoal->id,
            'section_id' => $section->id,
            'part_soal_id' => $partSoal->id,
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
            'part_soal_id' => $partSoal->id,
        ]);
    }

    public function test_jalur_tambah_soal_satuan_menolak_section_dari_program_tes_lain(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = $this->bankSoal();
        $programTesLain = ProgramTes::create([
            'kode' => '010',
            'nama' => 'Program Tes Relasi Soal',
            'batas_pelanggaran' => 3,
            'aktif' => true,
        ]);
        $sectionLain = Section::create([
            'program_tes_id' => $programTesLain->id,
            'kode' => '010001',
            'nama' => 'Reading',
            'urutan' => 1,
            'aktif' => true,
        ]);

        $this->post('/admin/ujian/soal', [
            'bank_soal_id' => $bankSoal->id,
            'section_id' => $sectionLain->id,
            'part_soal_id' => $this->partMembaca()->id,
            'teks_soal' => 'Soal lintas program tes',
            'pilihan_a' => 'A',
            'pilihan_b' => 'B',
            'pilihan_c' => 'C',
            'pilihan_d' => 'D',
            'kunci_jawaban' => 'A',
            'urutan' => 1,
        ])->assertSessionHasErrors('section_id');

        $this->assertDatabaseMissing('soal', ['teks_soal' => 'Soal lintas program tes']);
    }

    public function test_materi_dari_bank_lain_ditolak(): void
    {
        $this->masukSebagai('admin');

        $bankSoalLain = BankSoal::create([
            'nama' => 'Bank Soal Lain',
            'program_tes_id' => ProgramTes::first()->id,
            'aktif' => true,
        ]);

        $materiBankLain = $this->buatMateriTeks($bankSoalLain);
        $section = Section::where('nama', 'Reading')->first();

        $this->post('/pustaka-soal', [
            'bank_soal_id' => $this->bankSoal()->id,
            'materi_soal_id' => $materiBankLain->id,
            'soal' => [
                [
                    'section_id' => $section->id,
                    'part_soal_id' => $this->partMembaca()->id,
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

    public function test_update_materi_audio_menolak_teks_pada_section_teks_terlarang(): void
    {
        $this->masukSebagai('admin');

        $materiSoal = $this->buatMateriAudio();
        $section = Section::where('nama', 'Listening')->firstOrFail();

        $this->post('/pustaka-soal', [
            'bank_soal_id' => $this->bankSoal()->id,
            'materi_soal_id' => $materiSoal->id,
            'soal' => [
                [
                    'section_id' => $section->id,
                    'part_soal_id' => $this->partMenyimak()->id,
                    'teks_soal' => null,
                    'pilihan_a' => null,
                    'pilihan_b' => null,
                    'pilihan_c' => null,
                    'pilihan_d' => null,
                    'kunci_jawaban' => 'A',
                ],
            ],
        ])->assertRedirect();

        $this->put("/pustaka-soal/materi-soal/{$materiSoal->id}", [
            'judul' => $materiSoal->judul,
            'isi_teks' => '<p>Materi audio tidak boleh berisi teks.</p>',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseHas('materi_soal', [
            'id' => $materiSoal->id,
            'isi_teks' => null,
        ]);
    }

    public function test_materi_soal_tidak_dapat_diperbarui_jika_bank_sudah_dipakai_ujian(): void
    {
        $this->masukSebagai('admin');

        $materiSoal = $this->buatMateriTeks($this->bankSoalTerkunci());

        $this->put("/pustaka-soal/materi-soal/{$materiSoal->id}", [
            'judul' => 'Judul Materi Terkunci',
            'isi_teks' => '<p>Perubahan tidak boleh tersimpan.</p>',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseHas('materi_soal', [
            'id' => $materiSoal->id,
            'judul' => 'Passage Teks Uji',
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

    public function test_media_materi_tidak_dapat_dihapus_jika_bank_sudah_dipakai_ujian(): void
    {
        $this->masukSebagai('admin');

        $materiSoal = $this->buatMateriAudio($this->bankSoalTerkunci());

        $this->delete("/pustaka-soal/materi-soal/{$materiSoal->id}/media/audio")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('media_materi_soal', [
            'materi_soal_id' => $materiSoal->id,
            'jenis_media' => 'audio',
        ]);
    }

    public function test_materi_soal_tidak_dapat_dihapus_jika_bank_sudah_dipakai_ujian(): void
    {
        $this->masukSebagai('admin');

        $materiSoal = $this->buatMateriTeks($this->bankSoalTerkunci());

        $this->delete("/pustaka-soal/materi-soal/{$materiSoal->id}")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('materi_soal', ['id' => $materiSoal->id]);
    }

    public function test_materi_soal_baru_tidak_dapat_dibuat_di_bank_yang_sudah_dipakai_ujian(): void
    {
        $this->masukSebagai('admin');
        $bankSoal = $this->bankSoalTerkunci();

        $this->post('/pustaka-soal/materi-soal', [
            'bank_soal_id' => $bankSoal->id,
            'judul' => 'Materi Baru Terkunci',
            'isi_teks' => '<p>Materi tidak boleh dibuat.</p>',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseMissing('materi_soal', [
            'bank_soal_id' => $bankSoal->id,
            'judul' => 'Materi Baru Terkunci',
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
        $section = Section::where('nama', 'Reading')->first();

        $this->post('/pustaka-soal', [
            'bank_soal_id' => $this->bankSoal()->id,
            'materi_soal_id' => $materiAudio->id,
            'soal' => [
                [
                    'section_id' => $section->id,
                    'part_soal_id' => $this->partMembaca()->id,
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

    public function test_section_tanpa_kebijakan_ditolak(): void
    {
        $this->masukSebagai('admin');

        $berbicara = Section::create([
            'program_tes_id' => $this->bankSoal()->program_tes_id,
            'kode' => '001003',
            'nama' => 'Speaking',
            'urutan' => 3,
            'aktif' => true,
        ]);

        $partSoal = PartSoal::create([
            'bank_soal_id' => $this->bankSoal()->id,
            'section_id' => $berbicara->id,
            'nama' => 'Part 1',
            'urutan' => 1,
            'aktif' => true,
        ]);

        $this->post('/pustaka-soal', [
            'bank_soal_id' => $this->bankSoal()->id,
            'soal' => [
                [
                    'section_id' => $berbicara->id,
                    'part_soal_id' => $partSoal->id,
                    'teks_soal' => 'Soal section tanpa policy',
                    'pilihan_a' => 'A',
                    'pilihan_b' => 'B',
                    'pilihan_c' => 'C',
                    'pilihan_d' => 'D',
                    'kunci_jawaban' => 'A',
                ],
            ],
        ])->assertSessionHasErrors('soal');

        $this->assertDatabaseMissing('soal', [
            'teks_soal' => 'Soal section tanpa policy',
        ]);
    }

    public function test_section_dinamis_dengan_kebijakan_audio_menerima_soal_menyimak(): void
    {
        $this->masukSebagai('admin');

        $berbicara = Section::create([
            'program_tes_id' => $this->bankSoal()->program_tes_id,
            'kode' => '001003',
            'nama' => 'Speaking Dinamis',
            'urutan' => 3,
            'aktif' => true,
        ]);

        $partSoal = PartSoal::create([
            'bank_soal_id' => $this->bankSoal()->id,
            'section_id' => $berbicara->id,
            'nama' => 'Part 1',
            'urutan' => 1,
            'aktif' => true,
        ]);

        // Policy dinamis: audio wajib — dibuat lewat data, bukan kode hardcoded.
        $kebijakan = KebijakanMateriSoal::create([
            'section_id' => $berbicara->id,
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
                    'section_id' => $berbicara->id,
                    'part_soal_id' => $partSoal->id,
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
            'section_id' => $berbicara->id,
        ]);
    }

    public function test_soal_tidak_dapat_memakai_part_dari_bank_lain(): void
    {
        $this->masukSebagai('admin');

        $bankSoalLain = BankSoal::create([
            'nama' => 'Bank Soal Lain',
            'program_tes_id' => ProgramTes::first()->id,
            'aktif' => true,
        ]);

        $section = Section::where('nama', 'Reading')->first();

        $partBankLain = PartSoal::create([
            'bank_soal_id' => $bankSoalLain->id,
            'section_id' => $section->id,
            'nama' => 'Part 1',
            'urutan' => 1,
            'aktif' => true,
        ]);

        $this->post('/pustaka-soal', [
            'bank_soal_id' => $this->bankSoal()->id,
            'soal' => [
                [
                    'section_id' => $section->id,
                    'part_soal_id' => $partBankLain->id,
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

        $section = Section::where('nama', 'Reading')->first();

        $this->post('/pustaka-soal', [
            'bank_soal_id' => $this->bankSoal()->id,
            'soal' => [
                [
                    'section_id' => $section->id,
                    'part_soal_id' => $this->partMembaca()->id,
                    'teks_soal' => 'Pertanyaan tanpa opsi lengkap',
                    'pilihan_a' => 'A',
                    'pilihan_b' => 'B',
                    'pilihan_c' => 'C',
                    'pilihan_d' => '',
                    'kunci_jawaban' => 'A',
                ],
            ],
        ])->assertSessionHasErrors('soal');

        $this->assertSame(
            0,
            Soal::where('bank_soal_id', $this->bankSoal()->id)->count(),
        );
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
