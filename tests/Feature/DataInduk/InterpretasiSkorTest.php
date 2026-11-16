<?php

namespace Tests\Feature\DataInduk;

use App\Models\InterpretasiSkor;
use App\Models\Pengguna;
use App\Models\ProgramTes;
use App\Models\SkemaPenilaian;
use App\Modules\Penilaian\Services\LayananAturanPenilaian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterpretasiSkorTest extends TestCase
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

    private function skemaDraft(ProgramTes $programTes): SkemaPenilaian
    {
        return app(LayananAturanPenilaian::class)->buatVersi($programTes);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/admin/data-induk/interpretasi-skor')->assertRedirect('/login');
    }

    public function test_peran_tanpa_izin_ditolak(): void
    {
        $this->masukSebagai('peserta');

        $this->get('/admin/data-induk/interpretasi-skor')->assertForbidden();
    }

    public function test_admin_dapat_melihat_ringkasan_dan_detail_per_program_tes(): void
    {
        $this->masukSebagai('admin');
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();

        $this->get('/admin/data-induk/interpretasi-skor')->assertOk();
        $this->get("/admin/data-induk/interpretasi-skor/program-tes/{$programTes->id}")->assertOk();
    }

    public function test_admin_dapat_membuka_halaman_tambah_massal(): void
    {
        $this->masukSebagai('admin');
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $skema = $this->skemaDraft($programTes);

        $this->get("/admin/data-induk/interpretasi-skor/program-tes/{$programTes->id}/tambah-massal?skema={$skema->id}")
            ->assertOk();
    }

    public function test_admin_dapat_memperbarui_interpretasi_skor(): void
    {
        $this->masukSebagai('admin');
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $skema = $this->skemaDraft($programTes);
        $interpretasiSkor = InterpretasiSkor::where('skema_penilaian_id', $skema->id)
            ->where('skor_minimum', 380)
            ->firstOrFail();

        $this->put("/admin/data-induk/interpretasi-skor/{$interpretasiSkor->id}", [
            'skema_penilaian_id' => $skema->id,
            'program_tes_id' => $programTes->id,
            'skor_minimum' => 381,
            'skor_maksimum' => 400,
            'tingkat_cefr' => 'B2',
            'label_tingkat' => 'Excellent Updated',
            'lulus' => true,
            'deskripsi' => 'Updated description',
        ])->assertRedirect();

        $this->assertDatabaseHas('interpretasi_skor', [
            'id' => $interpretasiSkor->id,
            'skor_minimum' => 381,
            'label_tingkat' => 'Excellent Updated',
            'deskripsi' => 'Updated description',
        ]);
    }

    public function test_rentang_skor_tidak_valid_ditolak_saat_memperbarui(): void
    {
        $this->masukSebagai('admin');
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $skema = $this->skemaDraft($programTes);
        $interpretasiSkor = InterpretasiSkor::where('skema_penilaian_id', $skema->id)
            ->where('skor_minimum', 380)
            ->firstOrFail();

        $this->put("/admin/data-induk/interpretasi-skor/{$interpretasiSkor->id}", [
            'skema_penilaian_id' => $skema->id,
            'program_tes_id' => $programTes->id,
            'skor_minimum' => 400,
            'skor_maksimum' => 399,
            'tingkat_cefr' => 'B2',
            'label_tingkat' => 'Invalid',
            'lulus' => true,
        ])->assertSessionHasErrors('skor_maksimum');
    }

    public function test_peran_tanpa_izin_tidak_dapat_memperbarui_interpretasi_skor(): void
    {
        $this->masukSebagai('peserta');
        $interpretasiSkor = InterpretasiSkor::where('skor_minimum', 380)->firstOrFail();
        $skema = SkemaPenilaian::findOrFail($interpretasiSkor->skema_penilaian_id);

        $this->put("/admin/data-induk/interpretasi-skor/{$interpretasiSkor->id}", [
            'skema_penilaian_id' => $skema->id,
            'program_tes_id' => $skema->program_tes_id,
            'skor_minimum' => 381,
            'skor_maksimum' => 400,
            'tingkat_cefr' => 'B2',
            'label_tingkat' => 'Unauthorized',
            'lulus' => true,
        ])->assertForbidden();
    }

    public function test_admin_dapat_menyimpan_interpretasi_skor_secara_massal(): void
    {
        $this->masukSebagai('admin');
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $skema = $this->skemaDraft($programTes);

        $this->post("/admin/data-induk/interpretasi-skor/program-tes/{$programTes->id}/tambah-massal", [
            'skema_penilaian_id' => $skema->id,
            'interpretasi_skor' => [
                [
                    'skor_minimum' => 401,
                    'skor_maksimum' => 449,
                    'tingkat_cefr' => 'C1',
                    'label_tingkat' => 'Advanced',
                    'lulus' => true,
                    'deskripsi' => 'Advanced level',
                ],
                [
                    'skor_minimum' => 450,
                    'skor_maksimum' => 499,
                    'tingkat_cefr' => 'C2',
                    'label_tingkat' => 'Proficient',
                    'lulus' => true,
                ],
            ],
        ])->assertRedirect(route('admin.data-induk.interpretasi-skor.program-tes.daftar', [
            'programTes' => $programTes->id,
            'skema' => $skema->id,
        ]));

        $this->assertDatabaseHas('interpretasi_skor', [
            'skema_penilaian_id' => $skema->id,
            'skor_minimum' => 401,
            'skor_maksimum' => 449,
            'label_tingkat' => 'Advanced',
        ]);
        $this->assertDatabaseHas('interpretasi_skor', [
            'skema_penilaian_id' => $skema->id,
            'skor_minimum' => 450,
            'skor_maksimum' => 499,
            'label_tingkat' => 'Proficient',
        ]);
    }

    public function test_bulk_interpretasi_skor_maksimal_sepuluh_data(): void
    {
        $this->masukSebagai('admin');
        $programTes = ProgramTes::where('nama', 'EPT')->firstOrFail();
        $skema = $this->skemaDraft($programTes);
        $interpretasiSkor = [];

        for ($index = 1; $index <= 11; $index++) {
            $minimum = 500 + ($index * 10);
            $interpretasiSkor[] = [
                'skor_minimum' => $minimum,
                'skor_maksimum' => $minimum + 9,
                'tingkat_cefr' => "C{$index}",
                'label_tingkat' => "Level {$index}",
                'lulus' => true,
            ];
        }

        $this->post("/admin/data-induk/interpretasi-skor/program-tes/{$programTes->id}/tambah-massal", [
            'skema_penilaian_id' => $skema->id,
            'interpretasi_skor' => $interpretasiSkor,
        ])->assertSessionHasErrors('interpretasi_skor');

        $this->assertDatabaseMissing('interpretasi_skor', [
            'skema_penilaian_id' => $skema->id,
            'skor_minimum' => 510,
        ]);
    }
}
