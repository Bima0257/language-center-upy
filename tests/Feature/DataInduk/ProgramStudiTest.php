<?php

namespace Tests\Feature\DataInduk;

use App\Models\Fakultas;
use App\Models\Pengguna;
use App\Models\ProgramStudi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramStudiTest extends TestCase
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

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/admin/data-induk/program-studi')->assertRedirect('/login');
    }

    public function test_peran_tanpa_izin_ditolak(): void
    {
        $this->masukSebagai('student');

        $this->get('/admin/data-induk/program-studi')->assertForbidden();
    }

    public function test_admin_dapat_mengakses_halaman(): void
    {
        $this->masukSebagai('admin');

        $this->get('/admin/data-induk/program-studi')->assertOk();
    }

    public function test_admin_dapat_mengelola_program_studi(): void
    {
        $this->masukSebagai('admin');

        $fakultas = Fakultas::firstOrFail();

        $this->post('/admin/data-induk/program-studi', [
            'fakultas_id' => $fakultas->id,
            'nama' => 'Program Studi Baru',
            'kode' => 'PSB',
            'aktif' => true,
        ])->assertRedirect();

        $programStudi = ProgramStudi::where('nama', 'Program Studi Baru')->firstOrFail();

        $this->assertDatabaseHas('program_studi', [
            'id' => $programStudi->id,
            'fakultas_id' => $fakultas->id,
            'kode' => 'PSB',
            'aktif' => true,
        ]);

        $this->put("/admin/data-induk/program-studi/{$programStudi->id}", [
            'fakultas_id' => $fakultas->id,
            'nama' => 'Program Studi Diperbarui',
            'kode' => 'PSD',
            'aktif' => false,
        ])->assertRedirect();

        $this->assertDatabaseHas('program_studi', [
            'id' => $programStudi->id,
            'nama' => 'Program Studi Diperbarui',
            'kode' => 'PSD',
            'aktif' => false,
        ]);

        $this->delete("/admin/data-induk/program-studi/{$programStudi->id}")->assertRedirect();

        $this->assertDatabaseMissing('program_studi', ['id' => $programStudi->id]);
    }

    public function test_admin_dapat_melihat_detail_program_studi_per_fakultas(): void
    {
        $this->masukSebagai('admin');

        $fakultas = Fakultas::firstOrFail();

        $this->get("/admin/data-induk/program-studi/fakultas/{$fakultas->id}")->assertOk();
        $this->get("/admin/data-induk/program-studi/fakultas/{$fakultas->id}/tambah-massal")->assertOk();
    }

    public function test_admin_dapat_menyimpan_program_studi_secara_bulk_dengan_fakultas_dari_route(): void
    {
        $this->masukSebagai('admin');

        $fakultas = Fakultas::firstOrFail();
        $fakultasLain = Fakultas::where('id', '!=', $fakultas->id)->firstOrFail();

        $this->post("/admin/data-induk/program-studi/fakultas/{$fakultas->id}/tambah-massal", [
            'program_studi' => [
                [
                    'fakultas_id' => $fakultasLain->id,
                    'nama' => 'Program Bulk Satu',
                    'kode' => 'PBS1',
                    'aktif' => true,
                ],
                [
                    'nama' => 'Program Bulk Dua',
                    'kode' => 'PBS2',
                    'aktif' => false,
                ],
            ],
        ])->assertRedirect(route('admin.data-induk.program-studi.fakultas.daftar', $fakultas));

        $this->assertDatabaseHas('program_studi', [
            'nama' => 'Program Bulk Satu',
            'kode' => 'PBS1',
            'fakultas_id' => $fakultas->id,
            'aktif' => true,
        ]);
        $this->assertDatabaseHas('program_studi', [
            'nama' => 'Program Bulk Dua',
            'kode' => 'PBS2',
            'fakultas_id' => $fakultas->id,
            'aktif' => false,
        ]);
    }

    public function test_bulk_program_studi_maksimal_sepuluh_data(): void
    {
        $this->masukSebagai('admin');

        $fakultas = Fakultas::firstOrFail();
        $programStudi = [];

        for ($index = 1; $index <= 11; $index++) {
            $programStudi[] = [
                'nama' => "Program Bulk {$index}",
                'kode' => "PB{$index}",
                'aktif' => true,
            ];
        }

        $this->post("/admin/data-induk/program-studi/fakultas/{$fakultas->id}/tambah-massal", [
            'program_studi' => $programStudi,
        ])->assertSessionHasErrors('program_studi');

        $this->assertDatabaseMissing('program_studi', ['kode' => 'PB1']);
    }

    public function test_bulk_program_studi_menolak_nama_yang_sudah_ada(): void
    {
        $this->masukSebagai('admin');

        $fakultas = Fakultas::firstOrFail();
        $existingProgramStudi = ProgramStudi::firstOrFail();

        $this->post("/admin/data-induk/program-studi/fakultas/{$fakultas->id}/tambah-massal", [
            'program_studi' => [[
                'nama' => $existingProgramStudi->nama,
                'kode' => 'KODEBARU',
                'aktif' => true,
            ]],
        ])->assertSessionHasErrors('program_studi.0.nama');

        $this->assertDatabaseMissing('program_studi', ['kode' => 'KODEBARU']);
    }

    public function test_fakultas_program_studi_tidak_dapat_diubah(): void
    {
        $this->masukSebagai('admin');

        $programStudi = ProgramStudi::firstOrFail();
        $fakultasAsalId = $programStudi->fakultas_id;
        $fakultasLain = Fakultas::where('id', '!=', $fakultasAsalId)->firstOrFail();

        $this->put("/admin/data-induk/program-studi/{$programStudi->id}", [
            'fakultas_id' => $fakultasLain->id,
            'nama' => $programStudi->nama,
            'kode' => $programStudi->kode,
            'aktif' => $programStudi->aktif,
        ])->assertRedirect();

        $this->assertDatabaseHas('program_studi', [
            'id' => $programStudi->id,
            'fakultas_id' => $fakultasAsalId,
        ]);
    }
}
