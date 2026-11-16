<?php

namespace Tests\Feature\Ujian;

use App\Models\Jawaban;
use App\Models\Pengguna;
use App\Models\ProgramTes;
use App\Models\Section;
use App\Models\SesiUjian;
use App\Models\Soal;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoUjianTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function masukSebagai(string $peran): Pengguna
    {
        $pengguna = Pengguna::where('email', $peran.'@toefl.test')->firstOrFail();

        $this->post('/login', [
            'email' => $pengguna->email,
            'password' => 'password',
        ]);

        return $pengguna;
    }

    /**
     * @return array<int, array{soal_id: string, jawaban: string}>
     */
    private function jawabanDemo(Ujian $ujian, int $benarListening, int $benarReading): array
    {
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $listening = Section::where('program_tes_id', $programTes->id)->where('nama', 'Listening')->firstOrFail();
        $reading = Section::where('program_tes_id', $programTes->id)->where('nama', 'Reading')->firstOrFail();

        $jawaban = [];

        foreach ([[$listening->id, $benarListening], [$reading->id, $benarReading]] as [$sectionId, $jumlahBenar]) {
            $soal = Soal::whereHas('partUjian', fn ($query) => $query->where('ujian_id', $ujian->id))
                ->where('section_id', $sectionId)
                ->orderBy('urutan')
                ->get();

            foreach ($soal as $index => $item) {
                $jawaban[] = [
                    'soal_id' => $item->id,
                    'jawaban' => $index < $jumlahBenar
                        ? $item->kunci_jawaban
                        : ($item->kunci_jawaban === 'A' ? 'B' : 'A'),
                ];
            }
        }

        return $jawaban;
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('admin.demo-ujian.daftar'))->assertRedirect('/login');

        $ujian = Ujian::where('judul', 'EPT Try Out 1')->firstOrFail();

        $this->get(route('admin.demo-ujian.mulai', $ujian))->assertRedirect('/login');
    }

    public function test_peran_tanpa_izin_ditolak(): void
    {
        $this->masukSebagai('peserta');

        $ujian = Ujian::where('judul', 'EPT Try Out 1')->firstOrFail();

        $this->get(route('admin.demo-ujian.daftar'))->assertForbidden();
        $this->get(route('admin.demo-ujian.mulai', $ujian))->assertForbidden();
    }

    public function test_admin_dan_superadmin_dapat_mengakses_halaman_daftar_demo(): void
    {
        foreach (['admin', 'superadmin'] as $peran) {
            $this->masukSebagai($peran);

            $this->get(route('admin.demo-ujian.daftar'))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component('Admin/DemoUjian/index')
                    ->has('ujian'));
        }
    }

    public function test_halaman_daftar_menampilkan_jumlah_part_dan_total_soal(): void
    {
        $this->masukSebagai('admin');

        $ujian = Ujian::where('judul', 'EPT Try Out 1')->firstOrFail();

        $page = $this->get(route('admin.demo-ujian.daftar'))->assertOk()->inertiaPage();
        $item = collect($page['props']['ujian'])->firstWhere('id', $ujian->id);

        $this->assertNotNull($item);
        $this->assertSame(
            $ujian->partUjian()->count(),
            (int) $item['part_ujian_count'],
        );
        $this->assertSame(
            (int) $ujian->partUjian()->sum('jumlah_soal'),
            (int) $item['total_soal'],
        );
    }

    public function test_admin_membuka_demo_memakai_ui_pratinjau_ujian(): void
    {
        $this->masukSebagai('admin');

        $ujian = Ujian::where('judul', 'EPT Try Out 1')->firstOrFail();

        $page = $this->get(route('admin.demo-ujian.mulai', $ujian))
            ->assertOk()
            ->inertiaPage();

        $this->assertSame('Ujian/PratinjauPengerjaan/index', $page['component']);
        $this->assertTrue($page['props']['demo']);
        $this->assertSame(route('admin.demo-ujian.daftar'), $page['props']['backUrl']);
        $this->assertSame(route('admin.ujian.detail', $ujian), $page['props']['manageUrl']);
        $this->assertSame(route('admin.demo-ujian.skor', $ujian), $page['props']['skorUrl']);
        $this->assertSame($ujian->judul, $page['props']['overview']['judulUjian']);
        $this->assertNotEmpty($page['props']['blocks']);
        $this->assertArrayNotHasKey('kunci_jawaban', $page['props']['blocks'][0]['soal'][0]);
    }

    public function test_demo_menghitung_skor_dengan_aturan_penilaian_aktif(): void
    {
        $this->masukSebagai('admin');

        $ujian = Ujian::where('judul', 'EPT Try Out 1')->firstOrFail();
        $jumlahSesi = SesiUjian::count();
        $jumlahJawaban = Jawaban::count();

        $response = $this->postJson(route('admin.demo-ujian.skor', $ujian), [
            'jawaban' => $this->jawabanDemo($ujian, 37, 43),
        ])->assertOk();

        $data = $response->json();
        $skorListening = collect($data['skor_section'])->firstWhere('nama_section', 'Listening');
        $skorReading = collect($data['skor_section'])->firstWhere('nama_section', 'Reading');

        $this->assertNotNull($skorListening);
        $this->assertNotNull($skorReading);
        $this->assertSame(37, $skorListening['jumlah_benar']);
        $this->assertSame(50, $skorListening['jumlah_soal']);
        $this->assertSame(174.0, (float) $skorListening['skor_skala']);
        $this->assertSame(43, $skorReading['jumlah_benar']);
        $this->assertSame(50, $skorReading['jumlah_soal']);
        $this->assertSame(186.0, (float) $skorReading['skor_skala']);
        $this->assertSame(360.0, (float) $data['total']);
        $this->assertSame(400.0, (float) $data['total_maksimum']);
        $this->assertTrue($data['lulus']);
        $this->assertSame('B2-', $data['tingkat_cefr']);
        $this->assertSame('Very Good', $data['label_tingkat']);

        $this->assertSame($jumlahSesi, SesiUjian::count());
        $this->assertSame($jumlahJawaban, Jawaban::count());
    }

    public function test_demo_skor_dengan_jawaban_kosong_dihitung_sebagai_salah(): void
    {
        $this->masukSebagai('admin');

        $ujian = Ujian::where('judul', 'EPT Try Out 1')->firstOrFail();

        $data = $this->postJson(route('admin.demo-ujian.skor', $ujian), [
            'jawaban' => [],
        ])->assertOk()->json();

        $this->assertSame(200.0, (float) $data['total']);
        $this->assertFalse($data['lulus']);
        $this->assertSame('A1', $data['tingkat_cefr']);
    }

    public function test_demo_skor_menolak_jawaban_tidak_valid(): void
    {
        $this->masukSebagai('admin');

        $ujian = Ujian::where('judul', 'EPT Try Out 1')->firstOrFail();
        $soal = Soal::whereHas('partUjian', fn ($query) => $query->where('ujian_id', $ujian->id))->firstOrFail();

        $this->postJson(route('admin.demo-ujian.skor', $ujian), [
            'jawaban' => [
                ['soal_id' => $soal->id, 'jawaban' => 'Z'],
            ],
        ])->assertUnprocessable()->assertJsonValidationErrors('jawaban.0.jawaban');
    }

    public function test_demo_skor_hanya_dapat_diakses_admin(): void
    {
        $ujian = Ujian::where('judul', 'EPT Try Out 1')->firstOrFail();

        $this->postJson(route('admin.demo-ujian.skor', $ujian), ['jawaban' => []])
            ->assertRedirect('/login');

        $this->masukSebagai('peserta');

        $this->postJson(route('admin.demo-ujian.skor', $ujian), ['jawaban' => []])
            ->assertForbidden();
    }

    public function test_membuka_demo_tidak_membuat_sesi_atau_jawaban(): void
    {
        $this->masukSebagai('admin');

        $ujian = Ujian::where('judul', 'EPT Try Out 1')->firstOrFail();
        $jumlahSesi = SesiUjian::count();
        $jumlahJawaban = Jawaban::count();

        $this->get(route('admin.demo-ujian.daftar'))->assertOk();
        $this->get(route('admin.demo-ujian.mulai', $ujian))->assertOk();

        $this->assertSame($jumlahSesi, SesiUjian::count());
        $this->assertSame($jumlahJawaban, Jawaban::count());
    }

    public function test_ujian_tanpa_soal_dialihkan_dengan_pesan_error(): void
    {
        $this->masukSebagai('admin');

        $ujian = Ujian::create([
            'program_tes_id' => ProgramTes::where('nama', 'EPT')->value('id'),
            'judul' => 'Ujian Tanpa Soal',
            'mode' => 'tryout',
            'durasi_menit' => 60,
            'aktif' => true,
        ]);

        $this->get(route('admin.demo-ujian.mulai', $ujian))
            ->assertRedirect(route('admin.demo-ujian.daftar'))
            ->assertSessionHas('error');
    }
}
