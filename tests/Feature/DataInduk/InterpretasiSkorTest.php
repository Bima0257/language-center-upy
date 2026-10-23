<?php

namespace Tests\Feature\DataInduk;

use App\Models\InterpretasiSkor;
use App\Models\JenisTes;
use App\Models\Pengguna;
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

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/admin/data-induk/interpretasi-skor')->assertRedirect('/login');
    }

    public function test_peran_tanpa_izin_ditolak(): void
    {
        $this->masukSebagai('student');

        $this->get('/admin/data-induk/interpretasi-skor')->assertForbidden();
    }

    public function test_admin_dapat_melihat_ringkasan_dan_detail_per_jenis_tes(): void
    {
        $this->masukSebagai('admin');
        $jenisTes = JenisTes::where('nama', 'TOEFL iBT')->firstOrFail();

        $this->get('/admin/data-induk/interpretasi-skor')->assertOk();
        $this->get("/admin/data-induk/interpretasi-skor/jenis-tes/{$jenisTes->id}")->assertOk();
    }

    public function test_admin_dapat_membuka_halaman_tambah_massal(): void
    {
        $this->masukSebagai('admin');
        $jenisTes = JenisTes::where('nama', 'TOEFL iBT')->firstOrFail();

        $this->get("/admin/data-induk/interpretasi-skor/jenis-tes/{$jenisTes->id}/tambah-massal")
            ->assertOk();
    }

    public function test_admin_dapat_memperbarui_interpretasi_skor(): void
    {
        $this->masukSebagai('admin');
        $interpretasiSkor = InterpretasiSkor::where('skor_minimum', 380)->firstOrFail();

        $this->put("/admin/data-induk/interpretasi-skor/{$interpretasiSkor->id}", [
            'jenis_tes_id' => $interpretasiSkor->jenis_tes_id,
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
        $interpretasiSkor = InterpretasiSkor::where('skor_minimum', 380)->firstOrFail();

        $this->put("/admin/data-induk/interpretasi-skor/{$interpretasiSkor->id}", [
            'jenis_tes_id' => $interpretasiSkor->jenis_tes_id,
            'skor_minimum' => 400,
            'skor_maksimum' => 399,
            'tingkat_cefr' => 'B2',
            'label_tingkat' => 'Invalid',
            'lulus' => true,
        ])->assertSessionHasErrors('skor_maksimum');
    }

    public function test_peran_tanpa_izin_tidak_dapat_memperbarui_interpretasi_skor(): void
    {
        $this->masukSebagai('student');
        $interpretasiSkor = InterpretasiSkor::where('skor_minimum', 380)->firstOrFail();

        $this->put("/admin/data-induk/interpretasi-skor/{$interpretasiSkor->id}", [
            'jenis_tes_id' => $interpretasiSkor->jenis_tes_id,
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
        $jenisTes = JenisTes::where('nama', 'TOEFL iBT')->firstOrFail();

        $this->post("/admin/data-induk/interpretasi-skor/jenis-tes/{$jenisTes->id}/tambah-massal", [
            'interpretasi_skor' => [
                [
                    'skor_minimum' => 100,
                    'skor_maksimum' => 149,
                    'tingkat_cefr' => 'A1-',
                    'label_tingkat' => 'Starter',
                    'lulus' => false,
                    'deskripsi' => 'Starter level',
                ],
                [
                    'skor_minimum' => 150,
                    'skor_maksimum' => 199,
                    'tingkat_cefr' => 'A1',
                    'label_tingkat' => 'Beginner Plus',
                    'lulus' => false,
                ],
            ],
        ])->assertRedirect(route('admin.data-induk.interpretasi-skor.jenis-tes.daftar', $jenisTes));

        $this->assertDatabaseHas('interpretasi_skor', [
            'jenis_tes_id' => $jenisTes->id,
            'skor_minimum' => 100,
            'skor_maksimum' => 149,
            'label_tingkat' => 'Starter',
        ]);
        $this->assertDatabaseHas('interpretasi_skor', [
            'jenis_tes_id' => $jenisTes->id,
            'skor_minimum' => 150,
            'skor_maksimum' => 199,
            'label_tingkat' => 'Beginner Plus',
        ]);
    }

    public function test_bulk_interpretasi_skor_maksimal_sepuluh_data(): void
    {
        $this->masukSebagai('admin');
        $jenisTes = JenisTes::where('nama', 'TOEFL iBT')->firstOrFail();
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

        $this->post("/admin/data-induk/interpretasi-skor/jenis-tes/{$jenisTes->id}/tambah-massal", [
            'interpretasi_skor' => $interpretasiSkor,
        ])->assertSessionHasErrors('interpretasi_skor');

        $this->assertDatabaseMissing('interpretasi_skor', [
            'jenis_tes_id' => $jenisTes->id,
            'skor_minimum' => 510,
        ]);
    }
}
