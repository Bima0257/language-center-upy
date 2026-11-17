<?php

namespace App\Modules\Penilaian\Services;

use App\Models\ProgramTes;
use App\Models\Section;
use App\Models\SkemaPenilaian;
use App\Modules\DataInduk\Repositories\Contracts\InterpretasiSkorRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\ProgramTesRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\SectionRepositoryInterface;
use App\Modules\Penilaian\Repositories\Contracts\AturanPenilaianRepositoryInterface;
use App\Modules\Penilaian\Repositories\Contracts\SkemaPenilaianRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class LayananAturanPenilaian
{
    public function __construct(
        private AturanPenilaianRepositoryInterface $aturanRepo,
        private SkemaPenilaianRepositoryInterface $skemaRepo,
        private ProgramTesRepositoryInterface $programTesRepo,
        private SectionRepositoryInterface $sectionRepo,
        private InterpretasiSkorRepositoryInterface $interpretasiRepo,
        private LayananKonversiSkor $layananKonversi,
        private LayananInterpretasiSkor $layananInterpretasi,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dataDaftar(): array
    {
        return [
            'programTes' => $this->programTesRepo->semuaDenganJumlahBankSoal(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dataDetail(ProgramTes $programTes): array
    {
        $skema = $this->skemaRepo->untukProgram($programTes->id);
        $aturanPerSection = $this->aturanRepo->semuaUntukSkema($skema->id)->keyBy('section_id');

        $aturanPenilaian = $this->sectionRepo
            ->semuaBerurutan($programTes->id)
            ->map(function (Section $section) use ($aturanPerSection): array {
                $aturan = $aturanPerSection->get($section->id);

                if ($aturan === null) {
                    return [
                        'id' => null,
                        'section_id' => $section->id,
                        'kode_section' => $section->kode,
                        'nama_section' => $section->nama,
                        'terkonfigurasi' => false,
                        'tabel_konversi' => [],
                        'skor_mentah_maksimum' => null,
                        'skor_skala_minimum' => null,
                        'skor_skala_maksimum' => null,
                    ];
                }

                return [
                    'id' => $aturan->id,
                    'section_id' => $section->id,
                    'kode_section' => $section->kode,
                    'nama_section' => $section->nama,
                    'terkonfigurasi' => true,
                    'tabel_konversi' => $aturan->tabel_konversi,
                    'skor_mentah_maksimum' => $aturan->skor_mentah_maksimum,
                    'skor_skala_minimum' => $aturan->skor_skala_minimum,
                    'skor_skala_maksimum' => $aturan->skor_skala_maksimum,
                ];
            })
            ->values()
            ->all();

        $totalMinimum = (int) collect($aturanPenilaian)->sum('skor_skala_minimum');
        $totalMaksimum = (int) collect($aturanPenilaian)->sum('skor_skala_maksimum');

        return [
            'programTes' => $programTes,
            'skema' => $skema,
            'aturanPenilaian' => $aturanPenilaian,
            'interpretasiSkor' => $this->interpretasiRepo->semuaUntukSkema($skema->id),
            'total' => [
                'minimum' => $totalMinimum,
                'maksimum' => $totalMaksimum,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dataInterpretasi(ProgramTes $programTes): array
    {
        $skema = $this->skemaRepo->untukProgram($programTes->id);

        return [
            'programTes' => $programTes,
            'skema' => $skema,
            'interpretasiSkor' => $this->interpretasiRepo->semuaUntukSkema($skema->id),
        ];
    }

    public function simpanAturan(
        SkemaPenilaian $skema,
        Section $section,
        array $data,
    ): void {
        if ($section->program_tes_id !== $skema->program_tes_id) {
            throw (new ModelNotFoundException)->setModel(Section::class, [$section->id]);
        }

        DB::transaction(function () use ($skema, $section, $data): void {
            $this->aturanRepo->updateOrCreate($skema->id, $section->id, [
                'program_tes_id' => $skema->program_tes_id,
                'kode_section' => $section->kode,
                'tabel_konversi' => $data['tabel_konversi'],
                'skor_mentah_maksimum' => $data['skor_mentah_maksimum'],
                'skor_skala_minimum' => $data['skor_skala_minimum'],
                'skor_skala_maksimum' => $data['skor_skala_maksimum'],
            ]);

            $this->perbaruiTotalSkema($skema);
        });
    }

    public function temukanSkema(string $skemaPenilaianId): SkemaPenilaian
    {
        $skema = $this->skemaRepo->temukanDenganRelasi($skemaPenilaianId);

        if ($skema === null) {
            throw (new ModelNotFoundException)->setModel(SkemaPenilaian::class, [$skemaPenilaianId]);
        }

        return $skema;
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshotProgram(string $programTesId): array
    {
        return $this->snapshot($this->skemaRepo->untukProgram($programTesId));
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(SkemaPenilaian $skema): array
    {
        $skema->loadMissing(['aturanPenilaian.section', 'interpretasiSkor']);

        $aturan = $skema->aturanPenilaian
            ->sortBy('kode_section')
            ->values()
            ->map(fn ($rule): array => [
                'section_id' => $rule->section_id,
                'kode_section' => $rule->kode_section,
                'nama_section' => $rule->section->nama,
                'tabel_konversi' => $rule->tabel_konversi ?? [],
                'skor_mentah_maksimum' => $rule->skor_mentah_maksimum,
                'skor_skala_minimum' => $rule->skor_skala_minimum,
                'skor_skala_maksimum' => $rule->skor_skala_maksimum,
            ])
            ->all();

        $interpretasi = $skema->interpretasiSkor
            ->sortBy('skor_minimum')
            ->values()
            ->map(fn ($rentang): array => [
                'skor_minimum' => $rentang->skor_minimum,
                'skor_maksimum' => $rentang->skor_maksimum,
                'tingkat_cefr' => $rentang->tingkat_cefr,
                'label_tingkat' => $rentang->label_tingkat,
                'lulus' => (bool) $rentang->lulus,
                'deskripsi' => $rentang->deskripsi,
            ])
            ->all();

        $totalMinimum = $skema->skala_total_minimum !== null
            ? (float) $skema->skala_total_minimum
            : (float) collect($aturan)->sum('skor_skala_minimum');

        $totalMaksimum = $skema->skala_total_maksimum !== null
            ? (float) $skema->skala_total_maksimum
            : (float) collect($aturan)->sum('skor_skala_maksimum');

        return [
            'skema_penilaian_id' => $skema->id,
            'program_tes_id' => $skema->program_tes_id,
            'metode_total' => $skema->metode_total->value,
            'skala_total_minimum' => $totalMinimum,
            'skala_total_maksimum' => $totalMaksimum,
            'aturan' => $aturan,
            'interpretasi' => $interpretasi,
        ];
    }

    /**
     * @param  array<string, int|string>  $jumlahBenar
     * @return array<string, mixed>
     */
    public function simulasi(SkemaPenilaian $skema, array $jumlahBenar): array
    {
        $snapshot = $this->snapshot($skema);
        $hasil = [];
        $skorSection = [];

        foreach ($snapshot['aturan'] as $aturan) {
            $benar = (int) ($jumlahBenar[$aturan['section_id']] ?? 0);
            $skor = $this->layananKonversi->konversi($aturan, $benar);

            $skorSection[] = $skor;
            $hasil[] = [
                'section_id' => $aturan['section_id'],
                'nama_section' => $aturan['nama_section'],
                'jumlah_benar' => $benar,
                'skor_skala' => $skor,
            ];
        }

        $total = round(array_sum($skorSection), 1);
        $interpretasi = $this->layananInterpretasi->cari($snapshot['interpretasi'], $total);

        return [
            'hasil' => $hasil,
            'total' => $total,
            'total_maksimum' => $snapshot['skala_total_maksimum'],
            'interpretasi' => $interpretasi,
            'lulus' => (bool) ($interpretasi['lulus'] ?? false),
        ];
    }

    private function perbaruiTotalSkema(SkemaPenilaian $skema): void
    {
        $aturan = $this->aturanRepo->semuaUntukSkema($skema->id);

        $this->skemaRepo->perbarui($skema, [
            'skala_total_minimum' => (int) $aturan->sum('skor_skala_minimum'),
            'skala_total_maksimum' => (int) $aturan->sum('skor_skala_maksimum'),
        ]);
    }
}
