<?php

namespace Tests\Feature\Ujian;

use App\Enums\ModeUjian;
use App\Enums\StatusPendaftaranUjian;
use App\Models\PendaftaranUjian;
use App\Models\Pengguna;
use App\Models\PeriodePendaftaranUjian;
use App\Models\PeriodeUjian;
use App\Models\ProgramStudi;
use App\Models\ProgramTes;
use App\Models\SlotUjian;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UjianResmiTest extends TestCase
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
     * @param  array<int, string>  $programStudiIds
     */
    private function buatUjian(
        string $mode = ModeUjian::OFFICIAL->value,
        string $lingkup = 'umum',
        array $programStudiIds = [],
    ): Ujian {
        $ujian = Ujian::create([
            'program_tes_id' => ProgramTes::where('nama', 'EPT')->firstOrFail()->id,
            'judul' => 'Ujian '.strtoupper($mode).' '.uniqid(),
            'deskripsi' => '<p>Deskripsi ujian resmi</p>',
            'mode' => $mode,
            'lingkup' => $lingkup,
            'durasi_menit' => 60,
            'aktif' => true,
        ]);

        if ($programStudiIds !== []) {
            $ujian->programStudi()->sync($programStudiIds);
        }

        return $ujian;
    }

    private function buatPeriode(Ujian $ujian, bool $pendaftaranAktif = true): PeriodeUjian
    {
        $periode = PeriodeUjian::create([
            'ujian_id' => $ujian->id,
            'judul' => 'Periode '.$ujian->judul,
            'tanggal_mulai' => now()->subDay()->toDateString(),
            'tanggal_selesai' => now()->addDays(2)->toDateString(),
            'aktif' => true,
        ]);

        PeriodePendaftaranUjian::create([
            'periode_ujian_id' => $periode->id,
            'nama' => 'Pendaftaran '.$ujian->judul,
            'dibuka_pada' => now()->subDay(),
            'ditutup_pada' => now()->addDays(2),
            'aktif' => $pendaftaranAktif,
        ]);

        SlotUjian::create([
            'periode_ujian_id' => $periode->id,
            'tanggal' => now()->addDay()->toDateString(),
            'waktu_mulai' => '08:00:00',
            'waktu_selesai' => '10:00:00',
            'toleransi_keterlambatan_menit' => 15,
            'kapasitas_peserta' => 30,
            'aktif' => true,
        ]);

        return $periode;
    }

    public function test_peserta_dapat_membuka_halaman_ujian_resmi(): void
    {
        $this->masukSebagai('peserta');
        $ujian = $this->buatUjian();
        $periode = $this->buatPeriode($ujian);

        $page = $this->get('/ujian/resmi')->assertOk()->inertiaPage();

        $this->assertSame('Ujian/Resmi/index', $page['component']);

        $periodeData = collect($page['props']['periodeUjian'])->firstWhere('id', $periode->id);

        $this->assertNotNull($periodeData);
        $this->assertSame('<p>Deskripsi ujian resmi</p>', $periodeData['ujian']['deskripsi']);
        $this->assertSame(60, $periodeData['ujian']['durasi_menit']);
    }

    public function test_halaman_ujian_resmi_tidak_menampilkan_tryout(): void
    {
        $this->masukSebagai('peserta');
        $tryout = $this->buatUjian(ModeUjian::TRYOUT->value);
        $periodeTryout = $this->buatPeriode($tryout);
        $ujianResmi = $this->buatUjian();
        $periodeResmi = $this->buatPeriode($ujianResmi);

        $page = $this->get('/ujian/resmi')->assertOk()->inertiaPage();
        $ids = collect($page['props']['periodeUjian'])->pluck('id');

        $this->assertTrue($ids->contains($periodeResmi->id));
        $this->assertFalse($ids->contains($periodeTryout->id));
    }

    public function test_halaman_ujian_resmi_hanya_menampilkan_ujian_resmi_sesuai_prodi(): void
    {
        $this->masukSebagai('peserta');
        $prodiLain = ProgramStudi::where('kode', 'TI')->firstOrFail();
        $ujian = $this->buatUjian(ModeUjian::OFFICIAL->value, 'prodi', [$prodiLain->id]);
        $periode = $this->buatPeriode($ujian);

        $page = $this->get('/ujian/resmi')->assertOk()->inertiaPage();

        $this->assertFalse(collect($page['props']['periodeUjian'])->contains(
            fn (array $item): bool => $item['id'] === $periode->id,
        ));
    }

    public function test_halaman_ujian_resmi_menampilkan_status_pendaftaran_peserta(): void
    {
        $peserta = $this->masukSebagai('peserta');
        $ujian = $this->buatUjian();
        $periode = $this->buatPeriode($ujian);
        $slot = $periode->slotUjian()->firstOrFail();

        PendaftaranUjian::create([
            'slot_ujian_id' => $slot->id,
            'pengguna_id' => $peserta->id,
            'status' => StatusPendaftaranUjian::APPROVED,
        ]);

        $page = $this->get('/ujian/resmi')->assertOk()->inertiaPage();
        $periodeData = collect($page['props']['periodeUjian'])->firstWhere('id', $periode->id);

        $this->assertNotNull($periodeData);
        $this->assertSame($slot->id, $periodeData['slot_ujian'][0]['id']);
        $this->assertSame('approved', $periodeData['slot_ujian'][0]['pendaftaran_status']);
    }

    public function test_periode_tetap_tampil_saat_pendaftaran_nonaktif_bila_peserta_terdaftar(): void
    {
        $peserta = $this->masukSebagai('peserta');
        $ujian = $this->buatUjian();
        $periode = $this->buatPeriode($ujian, pendaftaranAktif: false);
        $slot = $periode->slotUjian()->firstOrFail();

        $ujianTanpaPendaftaran = $this->buatUjian();
        $periodeTanpaPendaftaran = $this->buatPeriode($ujianTanpaPendaftaran, pendaftaranAktif: false);

        PendaftaranUjian::create([
            'slot_ujian_id' => $slot->id,
            'pengguna_id' => $peserta->id,
            'status' => StatusPendaftaranUjian::APPROVED,
        ]);

        $page = $this->get('/ujian/resmi')->assertOk()->inertiaPage();
        $ids = collect($page['props']['periodeUjian'])->pluck('id');

        $this->assertTrue($ids->contains($periode->id));
        $this->assertFalse($ids->contains($periodeTanpaPendaftaran->id));
    }

    public function test_admin_tidak_dapat_membuka_halaman_ujian_resmi(): void
    {
        $this->masukSebagai('admin');

        $this->get('/ujian/resmi')->assertForbidden();
    }
}
