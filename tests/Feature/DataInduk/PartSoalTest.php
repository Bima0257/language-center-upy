<?php

namespace Tests\Feature\DataInduk;

use App\Models\BankSoal;
use App\Models\PartSoal;
use App\Models\PartSoalUjian;
use App\Models\PartUjian;
use App\Models\Pengguna;
use App\Models\ProgramTes;
use App\Models\Section;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartSoalTest extends TestCase
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

    private function buatUjianDenganPart(BankSoal $bankSoal, Section $section, bool $aktif): PartUjian
    {
        $ujian = Ujian::create([
            'program_tes_id' => $bankSoal->program_tes_id,
            'judul' => 'Ujian Sinkronisasi '.($aktif ? 'Aktif' : 'Nonaktif').' '.uniqid(),
            'mode' => 'tryout',
            'durasi_menit' => 60,
            'aktif' => $aktif,
        ]);

        return PartUjian::create([
            'ujian_id' => $ujian->id,
            'bank_soal_id' => $bankSoal->id,
            'section_id' => $section->id,
            'judul' => $section->nama,
            'urutan' => 1,
            'jumlah_soal' => 0,
        ]);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/admin/data-induk/part-soal')->assertRedirect('/login');
    }

    public function test_peran_tanpa_izin_ditolak(): void
    {
        $this->masukSebagai('peserta');

        $this->get('/admin/data-induk/part-soal')->assertForbidden();
    }

    public function test_admin_dapat_mengakses_halaman(): void
    {
        $this->masukSebagai('admin');

        $this->get('/admin/data-induk/part-soal')->assertOk();
    }

    public function test_admin_dapat_menavigasi_hierarki_program_tes_bank_dan_part_soal(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal Latihan')->firstOrFail();
        $programTes = $bankSoal->programTes;
        $root = $this->get('/admin/data-induk/part-soal')
            ->assertOk()
            ->inertiaPage();

        $bank = $this->get('/admin/data-induk/part-soal?program_tes_id='.$programTes->id)
            ->assertOk()
            ->inertiaPage();

        $bankIds = collect($bank['props']['bankSoal'])->pluck('id')->map(fn ($id) => (string) $id);
        $partSoal = collect($bank['props']['partSoal']);

        $this->assertNull($root['props']['programTesTerpilihId']);
        $this->assertTrue(collect($root['props']['programTes'])->contains(fn (array $jenis) => $jenis['id'] === $programTes->id));
        $this->assertSame($programTes->id, $bank['props']['programTesTerpilihId']);
        $this->assertTrue($bankIds->contains((string) $bankSoal->id));
        $this->assertTrue(
            $partSoal->every(fn (array $part) => $bankIds->contains((string) $part['bank_soal_id'])),
            'Daftar part pada detail program tes harus hanya berasal dari bank soal program tes tersebut.',
        );
    }

    public function test_admin_dapat_melihat_ringkasan_dan_detail_part_soal_per_bank(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal Latihan')->first();
        $ringkasan = $this->get('/admin/data-induk/part-soal')
            ->assertOk()
            ->inertiaPage();

        $detail = $this->get('/admin/data-induk/part-soal?bank_id='.$bankSoal->id)
            ->assertOk()
            ->inertiaPage();

        $partSoal = collect($detail['props']['partSoal']);

        $this->assertNull($ringkasan['props']['bankSoalTerpilihId']);
        $this->assertSame($bankSoal->id, $detail['props']['bankSoalTerpilihId']);
        $this->assertSame($bankSoal->program_tes_id, $detail['props']['programTesTerpilihId']);
        $this->assertTrue(
            $partSoal->every(fn (array $part) => (string) $part['bank_soal_id'] === (string) $bankSoal->id),
            'Detail harus hanya memuat part soal dari bank terpilih.',
        );
        $this->assertTrue(
            collect($ringkasan['props']['bankSoal'])->contains(fn (array $bank) => $bank['id'] === $bankSoal->id),
            'Ringkasan harus memuat bank soal yang digunakan untuk detail.',
        );
    }

    public function test_admin_dapat_menyaring_part_berdasarkan_bank(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal Latihan')->first();

        $this->get('/admin/data-induk/part-soal?bank_id='.$bankSoal->id)->assertOk();
    }

    public function test_pembuatan_part_mewajibkan_bank(): void
    {
        $this->masukSebagai('admin');

        $section = Section::where('nama', 'Reading')->first();

        $this->post('/admin/data-induk/part-soal', [
            'section_id' => $section->id,
            'nama' => 'Part X',
            'urutan' => 1,
        ])->assertSessionHasErrors('bank_soal_id');
    }

    public function test_admin_dapat_membuat_part_untuk_bank(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal Latihan')->first();
        $section = Section::where('nama', 'Reading')->first();

        $this->post('/admin/data-induk/part-soal', [
            'bank_soal_id' => $bankSoal->id,
            'section_id' => $section->id,
            'nama' => 'Part Baru',
            'urutan' => 9,
        ])->assertRedirect();

        $this->assertDatabaseHas('part_soal', [
            'bank_soal_id' => $bankSoal->id,
            'section_id' => $section->id,
            'nama' => 'Part Baru',
        ]);
    }

    public function test_part_baru_hanya_disinkronkan_ke_ujian_nonaktif(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal Latihan')->firstOrFail();
        $section = Section::where('nama', 'Reading')->firstOrFail();
        $partUjianNonaktif = $this->buatUjianDenganPart($bankSoal, $section, false);
        $partUjianAktif = $this->buatUjianDenganPart($bankSoal, $section, true);

        $this->post('/admin/data-induk/part-soal', [
            'bank_soal_id' => $bankSoal->id,
            'section_id' => $section->id,
            'nama' => 'Part Sinkron Nonaktif',
            'petunjuk' => 'Petunjuk baru.',
            'aktif' => true,
        ])->assertRedirect();

        $partSoal = PartSoal::where('nama', 'Part Sinkron Nonaktif')->firstOrFail();

        $this->assertDatabaseHas('part_soal_ujian', [
            'part_ujian_id' => $partUjianNonaktif->id,
            'part_soal_id' => $partSoal->id,
            'urutan' => $partSoal->urutan,
        ]);
        $this->assertDatabaseMissing('part_soal_ujian', [
            'part_ujian_id' => $partUjianAktif->id,
            'part_soal_id' => $partSoal->id,
        ]);
    }

    public function test_metadata_part_ditolak_jika_dipakai_ujian_aktif(): void
    {
        $this->masukSebagai('admin');

        $partSoal = PartSoal::where('nama', 'Listening Part 1')
            ->where('bank_soal_id', BankSoal::where('nama', 'Bank Soal Latihan')->value('id'))
            ->firstOrFail();
        $partUjian = $this->buatUjianDenganPart($partSoal->bankSoal, $partSoal->section, true);
        PartSoalUjian::create([
            'part_ujian_id' => $partUjian->id,
            'part_soal_id' => $partSoal->id,
            'urutan' => 1,
        ]);

        $this->put(route('admin.data-induk.part-soal.perbarui', $partSoal), [
            'bank_soal_id' => $partSoal->bank_soal_id,
            'section_id' => $partSoal->section_id,
            'nama' => 'Nama Terlindungi',
            'petunjuk' => 'Petunjuk baru.',
            'aktif' => $partSoal->aktif,
        ])->assertSessionHasErrors('part_soal');

        $this->assertDatabaseHas('part_soal', [
            'id' => $partSoal->id,
            'nama' => $partSoal->nama,
        ]);
    }

    public function test_metadata_part_nonaktif_dapat_diperbarui_dan_pivot_tetap_sinkron(): void
    {
        $this->masukSebagai('admin');

        $partSoal = PartSoal::where('nama', 'Listening Part 1')
            ->where('bank_soal_id', BankSoal::where('nama', 'Bank Soal Latihan')->value('id'))
            ->firstOrFail();
        $partUjian = $this->buatUjianDenganPart($partSoal->bankSoal, $partSoal->section, false);
        PartSoalUjian::create([
            'part_ujian_id' => $partUjian->id,
            'part_soal_id' => $partSoal->id,
            'urutan' => 99,
        ]);

        $this->put(route('admin.data-induk.part-soal.perbarui', $partSoal), [
            'bank_soal_id' => $partSoal->bank_soal_id,
            'section_id' => $partSoal->section_id,
            'nama' => 'Part Diperbarui Nonaktif',
            'petunjuk' => 'Petunjuk diperbarui.',
            'aktif' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('part_soal', [
            'id' => $partSoal->id,
            'nama' => 'Part Diperbarui Nonaktif',
        ]);
        $this->assertDatabaseHas('part_soal_ujian', [
            'part_ujian_id' => $partUjian->id,
            'part_soal_id' => $partSoal->id,
            'urutan' => PartSoal::findOrFail($partSoal->id)->urutan,
        ]);
    }

    public function test_part_dihapus_dari_pivot_ujian_nonaktif(): void
    {
        $this->masukSebagai('admin');

        $partSoal = PartSoal::where('nama', 'Listening Part 1')
            ->where('bank_soal_id', BankSoal::where('nama', 'Bank Soal Latihan')->value('id'))
            ->firstOrFail();
        $partUjian = $this->buatUjianDenganPart($partSoal->bankSoal, $partSoal->section, false);
        PartSoalUjian::create([
            'part_ujian_id' => $partUjian->id,
            'part_soal_id' => $partSoal->id,
            'urutan' => 1,
        ]);

        $this->delete(route('admin.data-induk.part-soal.hapus', $partSoal))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('part_soal', ['id' => $partSoal->id]);
        $this->assertDatabaseMissing('part_soal_ujian', [
            'part_ujian_id' => $partUjian->id,
            'part_soal_id' => $partSoal->id,
        ]);
    }

    public function test_part_yang_dipakai_ujian_aktif_tidak_dapat_dihapus(): void
    {
        $this->masukSebagai('admin');

        $partSoal = PartSoal::where('nama', 'Listening Part 1')
            ->where('bank_soal_id', BankSoal::where('nama', 'Bank Soal Latihan')->value('id'))
            ->firstOrFail();
        $partUjian = $this->buatUjianDenganPart($partSoal->bankSoal, $partSoal->section, true);
        PartSoalUjian::create([
            'part_ujian_id' => $partUjian->id,
            'part_soal_id' => $partSoal->id,
            'urutan' => 1,
        ]);

        $this->delete(route('admin.data-induk.part-soal.hapus', $partSoal))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('part_soal', ['id' => $partSoal->id]);
        $this->assertDatabaseHas('part_soal_ujian', [
            'part_ujian_id' => $partUjian->id,
            'part_soal_id' => $partSoal->id,
        ]);
    }

    public function test_penggunaan_ujian_aktif_mengunci_tampilan_part(): void
    {
        $this->masukSebagai('admin');

        $partSoal = PartSoal::where('nama', 'Listening Part 1')
            ->where('bank_soal_id', BankSoal::where('nama', 'Bank Soal Latihan')->value('id'))
            ->firstOrFail();
        $partUjian = $this->buatUjianDenganPart($partSoal->bankSoal, $partSoal->section, true);
        PartSoalUjian::create([
            'part_ujian_id' => $partUjian->id,
            'part_soal_id' => $partSoal->id,
            'urutan' => 1,
        ]);

        $halaman = $this->get('/admin/data-induk/part-soal?bank_id='.$partSoal->bank_soal_id)
            ->assertOk()
            ->inertiaPage();

        $part = collect($halaman['props']['partSoal'])
            ->firstWhere('id', $partSoal->id);

        $this->assertTrue($part['dipakai_ujian_aktif']);
    }

    public function test_nama_part_duplikat_pada_bank_dan_section_sama_ditolak(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal Latihan')->first();
        $section = Section::where('nama', 'Reading')->first();
        $existing = PartSoal::where('bank_soal_id', $bankSoal->id)->where('section_id', $section->id)->first();

        $this->post('/admin/data-induk/part-soal', [
            'bank_soal_id' => $bankSoal->id,
            'section_id' => $section->id,
            'nama' => $existing->nama,
            'urutan' => 1,
        ])->assertSessionHasErrors('nama');
    }

    public function test_nama_part_sama_diperbolehkan_pada_bank_lain(): void
    {
        $this->masukSebagai('admin');

        $bankSoal = BankSoal::where('nama', 'Bank Soal Latihan')->first();
        $section = Section::where('nama', 'Reading')->first();
        $existing = PartSoal::where('bank_soal_id', $bankSoal->id)->where('section_id', $section->id)->first();

        $bankSoalLain = BankSoal::create([
            'nama' => 'Bank Baru',
            'program_tes_id' => ProgramTes::first()->id,
            'aktif' => true,
        ]);

        $this->post('/admin/data-induk/part-soal', [
            'bank_soal_id' => $bankSoalLain->id,
            'section_id' => $section->id,
            'nama' => $existing->nama,
            'urutan' => 1,
        ])->assertRedirect();
    }
}
