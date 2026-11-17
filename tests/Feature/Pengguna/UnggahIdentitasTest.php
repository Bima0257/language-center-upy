<?php

namespace Tests\Feature\Pengguna;

use App\Models\Fakultas;
use App\Models\Pengguna;
use App\Models\ProfilPeserta;
use App\Models\ProgramStudi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UnggahIdentitasTest extends TestCase
{
    use RefreshDatabase;

    private Pengguna $pengguna;

    private Fakultas $fakultas;

    private ProgramStudi $programStudi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Storage::fake('public');

        $this->pengguna = Pengguna::where('email', 'peserta@toefl.test')->firstOrFail();
        $this->fakultas = Fakultas::where('kode', 'FKIP')->firstOrFail();
        $this->programStudi = ProgramStudi::where('kode', 'PBI')->firstOrFail();

        $this->post('/login', [
            'email' => $this->pengguna->email,
            'password' => 'password',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function dataIdentitas(): array
    {
        return [
            'jenis_peserta' => 'mahasiswa',
            'nomor_identitas' => '2200010001',
            'fakultas_id' => $this->fakultas->id,
            'program_studi_id' => $this->programStudi->id,
            'tahun_mulai' => 2022,
        ];
    }

    public function test_foto_identitas_lebih_dari_dua_mb_ditolak(): void
    {
        $this->post('/onboarding/upload-identity', [
            ...$this->dataIdentitas(),
            'foto_identitas' => UploadedFile::fake()->image('identitas.jpg')->size(3000),
        ])->assertSessionHasErrors('foto_identitas');
    }

    public function test_foto_identitas_dan_foto_disimpan_terkompresi(): void
    {
        $this->post('/onboarding/upload-identity', [
            ...$this->dataIdentitas(),
            'foto_identitas' => UploadedFile::fake()->image('identitas.jpg', 1600, 1200),
            'foto' => UploadedFile::fake()->image('profil.jpg', 800, 800),
        ])->assertRedirect(route('onboarding.verify-identity'));

        $profil = ProfilPeserta::where('pengguna_id', $this->pengguna->id)->firstOrFail();

        $this->assertNotNull($profil->foto_identitas);
        $this->assertStringEndsWith('.webp', $profil->foto_identitas);
        $this->assertTrue(Storage::disk('public')->exists($profil->foto_identitas));
        $this->assertLessThanOrEqual(
            2 * 1024 * 1024,
            Storage::disk('public')->size($profil->foto_identitas),
        );

        $this->pengguna->refresh();

        $this->assertNotNull($this->pengguna->foto);
        $this->assertStringEndsWith('.webp', $this->pengguna->foto);
        $this->assertTrue(Storage::disk('public')->exists($this->pengguna->foto));
        $this->assertLessThanOrEqual(
            2 * 1024 * 1024,
            Storage::disk('public')->size($this->pengguna->foto),
        );
    }

    public function test_dosen_wajib_mengisi_fakultas_dan_prodi(): void
    {
        $this->post('/logout');
        $this->post('/login', [
            'email' => 'dosen@toefl.test',
            'password' => 'password',
        ]);

        $this->post('/onboarding/upload-identity', [
            'nomor_identitas' => 'NIDN-2200010002',
        ])->assertSessionHasErrors(['fakultas_id', 'program_studi_id']);
    }

    public function test_dosen_dapat_melengkapi_identitas_dengan_fakultas_dan_prodi(): void
    {
        $this->post('/logout');
        $this->post('/login', [
            'email' => 'dosen@toefl.test',
            'password' => 'password',
        ]);

        $this->post('/onboarding/upload-identity', [
            'nomor_identitas' => 'NIDN-2200010002',
            'fakultas_id' => $this->fakultas->id,
            'program_studi_id' => $this->programStudi->id,
        ])->assertRedirect(route('onboarding.verify-identity'));

        $this->assertDatabaseHas('profil_peserta', [
            'jenis_peserta' => 'dosen',
            'nomor_identitas' => 'NIDN-2200010002',
            'fakultas_id' => $this->fakultas->id,
            'program_studi_id' => $this->programStudi->id,
        ]);
    }

    public function test_umum_wajib_mengisi_institusi(): void
    {
        $this->post('/logout');
        $this->post('/login', [
            'email' => 'umum@toefl.test',
            'password' => 'password',
        ]);

        $this->post('/onboarding/upload-identity', [
            'nomor_identitas' => 'NIK-3400000000000001',
        ])->assertSessionHasErrors('institusi');
    }

    public function test_umum_dapat_melengkapi_identitas_dengan_institusi(): void
    {
        $this->post('/logout');
        $this->post('/login', [
            'email' => 'umum@toefl.test',
            'password' => 'password',
        ]);

        $this->post('/onboarding/upload-identity', [
            'nomor_identitas' => 'NIK-3400000000000001',
            'institusi' => 'Masyarakat Umum',
        ])->assertRedirect(route('onboarding.verify-identity'));

        $this->assertDatabaseHas('profil_peserta', [
            'jenis_peserta' => 'umum',
            'nomor_identitas' => 'NIK-3400000000000001',
            'institusi' => 'Masyarakat Umum',
        ]);
    }
}
