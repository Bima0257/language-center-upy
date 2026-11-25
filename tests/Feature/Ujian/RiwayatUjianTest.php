<?php

namespace Tests\Feature\Ujian;

use App\Enums\ModeUjian;
use App\Enums\StatusSesi;
use App\Models\Pengguna;
use App\Models\PeriodeUjian;
use App\Models\ProgramTes;
use App\Models\SesiUjian;
use App\Models\SlotUjian;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiwayatUjianTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function peserta(): Pengguna
    {
        return Pengguna::where('email', 'peserta@toefl.test')->firstOrFail();
    }

    private function buatUjian(string $judul, ModeUjian $mode): Ujian
    {
        return Ujian::create([
            'program_tes_id' => ProgramTes::where('nama', 'EPT')->firstOrFail()->id,
            'judul' => $judul,
            'mode' => $mode->value,
            'durasi_menit' => 60,
            'aktif' => true,
        ]);
    }

    private function buatSesiResmi(Pengguna $pengguna, Ujian $ujian, StatusSesi $status): SesiUjian
    {
        $periode = PeriodeUjian::create([
            'ujian_id' => $ujian->id,
            'judul' => 'Periode '.$ujian->judul,
            'tanggal_mulai' => now()->subDay()->toDateString(),
            'tanggal_selesai' => now()->addDay()->toDateString(),
            'aktif' => true,
        ]);

        $slot = SlotUjian::create([
            'periode_ujian_id' => $periode->id,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '08:00:00',
            'waktu_selesai' => '10:00:00',
            'toleransi_keterlambatan_menit' => 15,
            'kapasitas_peserta' => 30,
            'aktif' => true,
        ]);

        return SesiUjian::create([
            'slot_ujian_id' => $slot->id,
            'ujian_id' => $ujian->id,
            'pengguna_id' => $pengguna->id,
            'status' => $status,
            'diserahkan_pada' => $status === StatusSesi::SUBMITTED ? now() : null,
            'dihentikan_pada' => $status === StatusSesi::TERMINATED ? now() : null,
            'skor_total' => 500.0,
            'skor_total_maksimum' => 677.0,
            'lulus' => true,
            'label_tingkat' => 'B2',
        ]);
    }

    private function buatSesiTryout(Pengguna $pengguna, Ujian $ujian, StatusSesi $status): SesiUjian
    {
        return SesiUjian::create([
            'slot_ujian_id' => null,
            'ujian_id' => $ujian->id,
            'pengguna_id' => $pengguna->id,
            'status' => $status,
            'diserahkan_pada' => $status === StatusSesi::SUBMITTED ? now() : null,
            'skor_total' => 480.0,
            'skor_total_maksimum' => 677.0,
            'lulus' => true,
        ]);
    }

    public function test_tab_resmi_hanya_menampilkan_sesi_ujian_resmi_yang_selesai(): void
    {
        $peserta = $this->peserta();
        $ujianResmi = $this->buatUjian('Ujian Resmi Riwayat', ModeUjian::OFFICIAL);
        $ujianTryout = $this->buatUjian('Tryout Riwayat', ModeUjian::TRYOUT);

        $sesiSelesai = $this->buatSesiResmi($peserta, $ujianResmi, StatusSesi::SUBMITTED);
        $this->buatSesiResmi($peserta, $ujianResmi, StatusSesi::IN_PROGRESS);
        $this->buatSesiTryout($peserta, $ujianTryout, StatusSesi::SUBMITTED);

        $this->actingAs($peserta);

        $page = $this->get(route('ujian.riwayat'))->assertOk()->inertiaPage();
        $ids = collect($page['props']['sesiUjian']['data'])->pluck('id')->all();

        $this->assertSame([$sesiSelesai->id], $ids);
        $this->assertSame('resmi', $page['props']['filters']['tab']);
    }

    public function test_tab_tryout_hanya_menampilkan_sesi_tryout_yang_selesai(): void
    {
        $peserta = $this->peserta();
        $ujianResmi = $this->buatUjian('Ujian Resmi Riwayat', ModeUjian::OFFICIAL);
        $ujianTryout = $this->buatUjian('Tryout Riwayat', ModeUjian::TRYOUT);

        $this->buatSesiResmi($peserta, $ujianResmi, StatusSesi::SUBMITTED);
        $sesiTryout = $this->buatSesiTryout($peserta, $ujianTryout, StatusSesi::TERMINATED);
        $this->buatSesiTryout($peserta, $ujianTryout, StatusSesi::IN_PROGRESS);

        $this->actingAs($peserta);

        $page = $this->get(route('ujian.riwayat', ['tab' => 'tryout']))->assertOk()->inertiaPage();
        $ids = collect($page['props']['sesiUjian']['data'])->pluck('id')->all();

        $this->assertSame([$sesiTryout->id], $ids);
        $this->assertSame('tryout', $page['props']['filters']['tab']);
    }

    public function test_riwayat_hanya_menampilkan_sesi_milik_pengguna_yang_login(): void
    {
        $peserta = $this->peserta();
        $pesertaLain = Pengguna::where('email', 'demo-peserta@toefl.test')->firstOrFail();
        $ujianResmi = $this->buatUjian('Ujian Resmi Riwayat', ModeUjian::OFFICIAL);

        $milikSendiri = $this->buatSesiResmi($peserta, $ujianResmi, StatusSesi::SUBMITTED);
        $this->buatSesiResmi($pesertaLain, $ujianResmi, StatusSesi::SUBMITTED);

        $this->actingAs($peserta);

        $page = $this->get(route('ujian.riwayat'))->assertOk()->inertiaPage();
        $ids = collect($page['props']['sesiUjian']['data'])->pluck('id')->all();

        $this->assertSame([$milikSendiri->id], $ids);
    }
}
