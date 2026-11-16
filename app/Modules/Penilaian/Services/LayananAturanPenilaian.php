<?php

namespace App\Modules\Penilaian\Services;

use App\Enums\StatusSkemaPenilaian;
use App\Models\ProgramTes;
use App\Models\Section;
use App\Models\SkemaPenilaian;
use App\Modules\DataInduk\Repositories\Contracts\InterpretasiSkorRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\ProgramTesRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\SectionRepositoryInterface;
use App\Modules\Penilaian\Repositories\Contracts\AturanPenilaianRepositoryInterface;
use App\Modules\Penilaian\Repositories\Contracts\SkemaPenilaianRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
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
    public function dataDetail(ProgramTes $programTes, ?string $skemaPenilaianId = null): array
    {
        $daftarSkema = $this->skemaRepo->semuaDenganProgramTes($programTes->id);
        $skema = $this->pilihSkema($programTes, $daftarSkema, $skemaPenilaianId);

        $aturanPerSection = $skema !== null
            ? $this->aturanRepo->semuaUntukSkema($skema->id)->keyBy('section_id')
            : collect();

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
            'daftarSkema' => $daftarSkema,
            'skemaTerpilih' => $skema,
            'aturanPenilaian' => $aturanPenilaian,
            'interpretasiSkor' => $skema !== null
                ? $this->interpretasiRepo->semuaUntukSkema($skema->id)
                : [],
            'total' => [
                'minimum' => $totalMinimum,
                'maksimum' => $totalMaksimum,
            ],
            'readOnly' => $skema === null || $skema->status !== StatusSkemaPenilaian::DRAFT,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dataInterpretasi(ProgramTes $programTes, ?string $skemaPenilaianId = null): array
    {
        $daftarSkema = $this->skemaRepo->semuaDenganProgramTes($programTes->id);
        $skema = $this->pilihSkema($programTes, $daftarSkema, $skemaPenilaianId);

        return [
            'programTes' => $programTes,
            'daftarSkema' => $daftarSkema,
            'skemaTerpilih' => $skema,
            'interpretasiSkor' => $skema !== null
                ? $this->interpretasiRepo->semuaUntukSkema($skema->id)
                : [],
            'readOnly' => $skema === null || $skema->status !== StatusSkemaPenilaian::DRAFT,
        ];
    }

    public function buatVersi(ProgramTes $programTes): SkemaPenilaian
    {
        return DB::transaction(function () use ($programTes): SkemaPenilaian {
            $versiTerakhir = $this->skemaRepo->versiTerakhir($programTes->id);

            $versi = 1;
            $metodeTotal = 'jumlah';
            $skalaTotalMinimum = null;
            $skalaTotalMaksimum = null;

            if ($versiTerakhir !== null) {
                $versi = $versiTerakhir->versi + 1;
                $metodeTotal = $versiTerakhir->metode_total;
                $skalaTotalMinimum = $versiTerakhir->skala_total_minimum;
                $skalaTotalMaksimum = $versiTerakhir->skala_total_maksimum;
            }

            $skema = $this->skemaRepo->buat([
                'program_tes_id' => $programTes->id,
                'versi' => $versi,
                'nama' => 'Skema '.$programTes->nama.' v'.$versi,
                'status' => StatusSkemaPenilaian::DRAFT,
                'metode_total' => $metodeTotal,
                'skala_total_minimum' => $skalaTotalMinimum,
                'skala_total_maksimum' => $skalaTotalMaksimum,
                'dibuat_oleh' => auth()->id(),
            ]);

            if ($versiTerakhir !== null) {
                $this->aturanRepo->salinKeSkema($versiTerakhir->id, $skema->id);
                $this->interpretasiRepo->salinKeSkema($versiTerakhir->id, $skema->id);
            }

            $this->perbaruiTotalSkema($skema);

            return $skema;
        });
    }

    public function simpanAturan(
        SkemaPenilaian $skema,
        Section $section,
        array $data,
    ): void {
        $this->pastikanSkemaDraft($skema);

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

    public function aktifkan(SkemaPenilaian $skema): void
    {
        DB::transaction(function () use ($skema): void {
            $skemaId = $skema->id;
            $skema = $this->skemaRepo->temukanDenganRelasi($skemaId);

            if ($skema === null) {
                throw (new ModelNotFoundException)->setModel(SkemaPenilaian::class, [$skemaId]);
            }

            if ($skema->status !== StatusSkemaPenilaian::DRAFT) {
                throw new \RuntimeException('Hanya skema berstatus draft yang dapat diaktifkan.');
            }

            $this->validasiSkema($skema);

            $this->skemaRepo->arsipkanSelain($skema->program_tes_id, $skema->id);

            $totalMinimum = (int) $skema->aturanPenilaian->sum('skor_skala_minimum');
            $totalMaksimum = (int) $skema->aturanPenilaian->sum('skor_skala_maksimum');

            $this->skemaRepo->perbarui($skema, [
                'status' => StatusSkemaPenilaian::AKTIF,
                'skala_total_minimum' => $totalMinimum,
                'skala_total_maksimum' => $totalMaksimum,
                'diaktifkan_pada' => now(),
                'diaktifkan_oleh' => auth()->id(),
            ]);

            activity()
                ->performedOn($skema)
                ->withProperties([
                    'program_tes_id' => $skema->program_tes_id,
                    'versi' => $skema->versi,
                    'total_minimum' => $totalMinimum,
                    'total_maksimum' => $totalMaksimum,
                ])
                ->log('skema_penilaian_activated');
        });
    }

    public function arsipkan(SkemaPenilaian $skema): void
    {
        if ($skema->status === StatusSkemaPenilaian::AKTIF) {
            throw new \RuntimeException('Versi aktif tidak dapat diarsipkan. Aktifkan versi lain terlebih dahulu.');
        }

        $this->skemaRepo->perbarui($skema, [
            'status' => StatusSkemaPenilaian::ARSIP,
        ]);
    }

    public function pastikanSkemaDraft(SkemaPenilaian $skema): void
    {
        if ($skema->status !== StatusSkemaPenilaian::DRAFT) {
            throw new \RuntimeException('Skema yang sudah aktif atau diarsipkan tidak dapat diubah. Buat versi baru untuk melakukan perubahan.');
        }
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
    public function snapshotAktif(string $programTesId): array
    {
        $skema = $this->skemaRepo->temukanAktif($programTesId);

        if ($skema === null) {
            throw new \RuntimeException('Skema penilaian aktif untuk program tes ini belum dikonfigurasi.');
        }

        return $this->snapshot($skema);
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
            'versi' => $skema->versi,
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

    /**
     * @param  Collection<int, SkemaPenilaian>  $daftarSkema
     */
    private function pilihSkema(
        ProgramTes $programTes,
        $daftarSkema,
        ?string $skemaPenilaianId,
    ): ?SkemaPenilaian {
        if ($skemaPenilaianId !== null && $skemaPenilaianId !== '') {
            $skema = $this->skemaRepo->temukanDenganRelasi($skemaPenilaianId);

            if ($skema === null || $skema->program_tes_id !== $programTes->id) {
                throw (new ModelNotFoundException)->setModel(SkemaPenilaian::class, [$skemaPenilaianId]);
            }

            return $skema;
        }

        return $daftarSkema->first(fn (SkemaPenilaian $skema): bool => $skema->status === StatusSkemaPenilaian::AKTIF)
            ?? $daftarSkema->first();
    }

    private function perbaruiTotalSkema(SkemaPenilaian $skema): void
    {
        $aturan = $this->aturanRepo->semuaUntukSkema($skema->id);

        $this->skemaRepo->perbarui($skema, [
            'skala_total_minimum' => (int) $aturan->sum('skor_skala_minimum'),
            'skala_total_maksimum' => (int) $aturan->sum('skor_skala_maksimum'),
        ]);
    }

    private function validasiSkema(SkemaPenilaian $skema): void
    {
        $skema->loadMissing(['aturanPenilaian.section', 'interpretasiSkor']);

        if ($skema->aturanPenilaian->isEmpty()) {
            throw new \RuntimeException('Skema penilaian harus memiliki minimal satu aturan section.');
        }

        $totalMinimum = 0;
        $totalMaksimum = 0;

        foreach ($skema->aturanPenilaian as $aturan) {
            if ($aturan->section_id === null) {
                throw new \RuntimeException('Terdapat aturan penilaian yang tidak terhubung ke section.');
            }

            $rawMaksimum = (int) $aturan->skor_mentah_maksimum;
            $skalaMinimum = (int) $aturan->skor_skala_minimum;
            $skalaMaksimum = (int) $aturan->skor_skala_maksimum;

            if ($rawMaksimum < 0 || $skalaMinimum > $skalaMaksimum) {
                throw new \RuntimeException(
                    "Rentang skor aturan {$aturan->kode_section} tidak valid.",
                );
            }

            $tabel = $aturan->tabel_konversi;
            $nilaiSebelumnya = null;

            for ($skorMentah = 0; $skorMentah <= $rawMaksimum; $skorMentah++) {
                if (! array_key_exists($skorMentah, $tabel)
                    && ! array_key_exists((string) $skorMentah, $tabel)) {
                    throw new \RuntimeException(
                        "Tabel konversi {$aturan->kode_section} belum lengkap untuk skor mentah {$skorMentah}.",
                    );
                }

                $nilai = (int) ($tabel[$skorMentah] ?? $tabel[(string) $skorMentah]);

                if ($nilai < $skalaMinimum || $nilai > $skalaMaksimum) {
                    throw new \RuntimeException(
                        "Nilai konversi {$aturan->kode_section} pada skor mentah {$skorMentah} berada di luar rentang skala.",
                    );
                }

                if ($nilaiSebelumnya !== null && $nilai < $nilaiSebelumnya) {
                    throw new \RuntimeException(
                        "Tabel konversi {$aturan->kode_section} harus monoton naik.",
                    );
                }

                $nilaiSebelumnya = $nilai;
            }

            $totalMinimum += $skalaMinimum;
            $totalMaksimum += $skalaMaksimum;
        }

        if ($skema->interpretasiSkor->isEmpty()) {
            throw new \RuntimeException('Skema penilaian harus memiliki minimal satu interpretasi skor.');
        }

        $interpretasi = $skema->interpretasiSkor->sortBy('skor_minimum')->values();
        $harapanMinimum = $totalMinimum;
        $sudahLulus = false;

        foreach ($interpretasi as $rentang) {
            if ((int) $rentang->skor_minimum !== $harapanMinimum) {
                throw new \RuntimeException(
                    'Rentang interpretasi skor harus berurutan tanpa celah atau tumpang tindih.',
                );
            }

            if ((int) $rentang->skor_maksimum < (int) $rentang->skor_minimum) {
                throw new \RuntimeException('Rentang interpretasi skor memiliki nilai maksimum yang tidak valid.');
            }

            if ($rentang->lulus) {
                $sudahLulus = true;
            } elseif ($sudahLulus) {
                throw new \RuntimeException('Status lulus pada interpretasi skor harus berurutan dari bawah ke atas.');
            }

            $harapanMinimum = (int) $rentang->skor_maksimum + 1;
        }

        if ($harapanMinimum - 1 !== $totalMaksimum) {
            throw new \RuntimeException(
                'Rentang interpretasi skor harus menutup seluruh rentang total '.$totalMinimum.'-'.$totalMaksimum.'.',
            );
        }

        if (! $sudahLulus) {
            throw new \RuntimeException('Skema penilaian harus memiliki minimal satu rentang yang lulus.');
        }
    }
}
