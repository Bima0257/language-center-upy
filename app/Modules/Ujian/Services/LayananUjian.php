<?php

namespace App\Modules\Ujian\Services;

use App\Models\BankSoal;
use App\Models\PartSoal;
use App\Models\PartUjian;
use App\Models\ProgramTes;
use App\Models\Section;
use App\Models\Soal;
use App\Models\Ujian;
use App\Modules\DataInduk\Repositories\Contracts\PartSoalRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\ProgramTesRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\SectionRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\BankSoalRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\PartUjianRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\SoalRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\UjianRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mews\Purifier\Facades\Purifier;

class LayananUjian
{
    public function __construct(
        private UjianRepositoryInterface $ujianRepo,
        private PartUjianRepositoryInterface $partRepo,
        private SoalRepositoryInterface $soalRepo,
        private BankSoalRepositoryInterface $bankSoalRepo,
        private ProgramTesRepositoryInterface $programTesRepo,
        private SectionRepositoryInterface $sectionRepo,
        private PartSoalRepositoryInterface $partSoalRepo,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dataDaftar(?string $programTesId = null): array
    {
        $programTes = $this->programTesRepo->semuaAktifBerurutan();
        $jumlahUjianPerProgramTes = $this->ujianRepo->jumlahPerProgramTes();
        $programTesTerpilih = $programTes->first(
            fn (ProgramTes $jenis): bool => $jenis->id === $programTesId,
        );

        return [
            'ujian' => $programTesTerpilih !== null
                ? $this->ujianRepo->semuaUntukDaftar($programTesTerpilih->id)
                : new Collection,
            'programTes' => $programTes,
            'ringkasanProgramTes' => $programTes->map(fn (ProgramTes $jenis): array => [
                'id' => $jenis->id,
                'kode' => $jenis->kode,
                'nama' => $jenis->nama,
                'jumlahUjian' => (int) $jumlahUjianPerProgramTes->get($jenis->id, 0),
            ])->values()->all(),
            'programTesTerpilih' => $programTesTerpilih,
            'programTesTerpilihId' => $programTesTerpilih?->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dataDemo(): array
    {
        return [
            'ujian' => $this->ujianRepo->semuaUntukDemo(),
        ];
    }

    public function memilikiSoal(Ujian $ujian): bool
    {
        return $this->ujianRepo->memilikiSoal($ujian);
    }

    public function temukanDenganRelasi(string $id): ?Ujian
    {
        return $this->ujianRepo->temukanDenganRelasi($id);
    }

    public function buatDenganPart(array $data): Ujian
    {
        $bank = $this->pastikanDataPembuatanUjian($data);
        $data = $this->bersihkanDeskripsi($data);

        return DB::transaction(function () use ($data, $bank) {
            $ujian = $this->ujianRepo->create($data);
            $bankId = $data['bank_soal_id'];

            $urutan = 1;
            foreach ($this->sectionRepo->semuaAktifBerurutan($ujian->program_tes_id) as $section) {
                $hasSoal = $this->soalRepo->soalDenganSectionUntukProgramTes(
                    $section->id,
                    $ujian->program_tes_id,
                    $bankId,
                )->isNotEmpty();

                if (! $hasSoal) {
                    continue;
                }

                $part = $this->partRepo->create([
                    'ujian_id' => $ujian->id,
                    'bank_soal_id' => $bankId,
                    'section_id' => $section->id,
                    'judul' => "{$section->nama} — {$bank->nama}",
                    'urutan' => $urutan++,
                ]);

                foreach ($this->partSoalRepo->semuaAktifDenganBank($bankId) as $partSoal) {
                    if ($partSoal->section_id !== $section->id) {
                        continue;
                    }

                    $this->partRepo->perbaruiAtauBuatPivotPartSoal($part->id, $partSoal->id, $partSoal->urutan);
                }

                $this->lampirkanSoal($ujian, $part, $section);
            }

            return $ujian;
        });
    }

    public function sinkronkanPart(Ujian $ujian): int
    {
        if ($ujian->aktif) {
            throw ValidationException::withMessages([
                'ujian' => 'Ujian aktif tidak dapat disinkronkan ulang.',
            ]);
        }

        $partSaatIni = $this->partRepo->semuaUntukUjian($ujian);
        $bankId = $partSaatIni->first()?->bank_soal_id;

        if ($bankId === null) {
            return 0;
        }

        $bank = $this->bankSoalRepo->find($bankId);
        if ($bank === null) {
            return 0;
        }

        return DB::transaction(function () use ($ujian, $bank, $bankId, $partSaatIni): int {
            $urutan = $partSaatIni->max('urutan') ?? 0;
            $dibuat = 0;
            $partSoalAktif = $this->partSoalRepo->semuaAktifDenganBank($bankId);

            foreach ($this->sectionRepo->semuaAktifBerurutan($ujian->program_tes_id) as $section) {
                $part = $partSaatIni->firstWhere('section_id', $section->id);
                $soalTersedia = $this->soalRepo->soalDenganSectionUntukProgramTes(
                    $section->id,
                    $ujian->program_tes_id,
                    $bankId,
                );

                if ($soalTersedia->isEmpty()) {
                    continue;
                }

                if ($part === null) {
                    $part = $this->partRepo->create([
                        'ujian_id' => $ujian->id,
                        'bank_soal_id' => $bankId,
                        'section_id' => $section->id,
                        'judul' => "{$section->nama} — {$bank->nama}",
                        'urutan' => ++$urutan,
                    ]);

                    foreach ($partSoalAktif as $partSoal) {
                        if ($partSoal->section_id === $section->id) {
                            $this->partRepo->perbaruiAtauBuatPivotPartSoal($part->id, $partSoal->id, $partSoal->urutan);
                        }
                    }

                    $partSaatIni->push($part);
                    $dibuat++;
                }

                $this->lampirkanSoal($ujian, $part, $section);
            }

            return $dibuat;
        });
    }

    /**
     * @param  array<string>  $partSoalIds
     * @return array<string>
     */
    public function idPartSoalDalamUjianAktif(array $partSoalIds): array
    {
        return $this->partRepo->idPartSoalDalamUjianAktif($partSoalIds);
    }

    /**
     * @param  Collection<int, PartSoal>  $partSoal
     */
    public function sinkronkanPartSoalGlobal(Collection $partSoal): void
    {
        $this->partRepo->hapusPartSoalDalamUjianNonaktif($partSoal->pluck('id')->all());
        $this->partRepo->sinkronkanPartSoalDalamUjianNonaktif($partSoal);
    }

    /**
     * @param  array<string>  $partSoalIds
     */
    public function hapusPartSoalDariUjianNonaktif(array $partSoalIds): void
    {
        $this->partRepo->hapusPartSoalDalamUjianNonaktif($partSoalIds);
    }

    public function perbarui(Ujian $ujian, array $data): Ujian
    {
        $data = $this->bersihkanDeskripsi($data);
        $targetProgramTesId = $data['program_tes_id'];
        $programTes = $this->programTesRepo->find($targetProgramTesId);

        if ($programTes === null) {
            throw ValidationException::withMessages([
                'program_tes_id' => 'Program tes tidak ditemukan.',
            ]);
        }

        if ($targetProgramTesId !== $ujian->program_tes_id) {
            if (! $programTes->aktif) {
                throw ValidationException::withMessages([
                    'program_tes_id' => 'Program tes tujuan harus aktif.',
                ]);
            }

            if ($ujian->partUjian()->exists() || $ujian->periodeUjian()->exists()) {
                throw ValidationException::withMessages([
                    'program_tes_id' => 'Program tes tidak dapat diubah setelah ujian memiliki part atau periode ujian.',
                ]);
            }
        }

        return $this->ujianRepo->update($ujian, $data);
    }

    public function hapus(Ujian $ujian): void
    {
        if ($this->ujianRepo->memilikiPeriodeUjian($ujian)) {
            throw new \RuntimeException('Ujian tidak bisa dihapus karena sudah memiliki jadwal.');
        }

        $this->ujianRepo->delete($ujian);
    }

    public function dataDetail(Ujian $ujian): array
    {
        if (! $ujian->aktif) {
            $this->sinkronkanPart($ujian);
        }
        $ujian = $this->ujianRepo->temukanDenganRelasi($ujian->id);
        $partIds = $ujian->partUjian->pluck('id');

        return [
            'ujian' => $ujian,
            'opsiSection' => $this->sectionRepo->semuaAktifBerurutan($ujian->program_tes_id)->map(fn (Section $section) => ['value' => $section->id, 'label' => $section->nama, 'kode' => $section->kode])->toArray(),
            'partSoal' => $this->partSoalRepo->semuaAktifBerurutan(),
            'bankSoal' => $this->bankSoalRepo->semuaAktifBerurutan(),
            'soalPerPart' => $this->partRepo->soalDikelompokkanDenganPart($partIds),
            'partSoalPerPart' => $this->partRepo->partSoalDikelompokkanDenganPart($partIds),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dataPratinjau(Ujian $ujian): array
    {
        $ujianDenganRelasi = $this->ujianRepo->temukanDenganRelasi($ujian->id);

        if ($ujianDenganRelasi === null) {
            throw (new ModelNotFoundException)->setModel(Ujian::class, [$ujian->id]);
        }

        $partUjian = $ujianDenganRelasi->partUjian;
        $soalPerPart = $this->partRepo->soalDikelompokkanDenganPart($partUjian->pluck('id'));
        $blocks = [];
        $nomorGlobal = 1;

        foreach ($partUjian as $part) {
            $kelompokSoal = [];
            $kunciBlokAktif = null;

            foreach ($soalPerPart->get($part->id, new Collection)->sortBy('urutan') as $barisSoal) {
                $soal = $barisSoal->soal;
                if ($soal === null) {
                    continue;
                }

                $partSoalId = (string) ($soal->part_soal_id ?? 'none');
                $kunciBlok = $partSoalId.'|'.($soal->materi_soal_id ?? 'none');

                if ($kunciBlok !== $kunciBlokAktif) {
                    $kunciBlokAktif = $kunciBlok;
                    $kelompokSoal[] = [
                        'partSoal' => $soal->partSoal,
                        'soal' => [],
                    ];
                }

                $kelompokSoal[array_key_last($kelompokSoal)]['soal'][] = $soal;
            }

            foreach ($kelompokSoal as $kelompok) {
                $soal = $kelompok['soal'];
                if ($soal === []) {
                    continue;
                }

                $partSoal = $kelompok['partSoal'];
                if ($partSoal === null) {
                    $namaPartSoal = 'Soal Standalone';
                    $petunjukPartSoal = null;
                } else {
                    $namaPartSoal = $partSoal->nama;
                    $petunjukPartSoal = $partSoal->petunjuk;
                }

                $blocks[] = [
                    'nomorMulai' => $nomorGlobal,
                    'jumlahSoal' => count($soal),
                    'materi' => $soal[0]->materiSoal?->url_audio !== null ? 'audio' : 'text',
                    'section' => [
                        'kode' => $part->section->kode,
                        'nama' => $part->section->nama,
                        'deskripsi' => $part->section->deskripsi,
                    ],
                    'partSoal' => [
                        'id' => $partSoal?->id,
                        'nama' => $namaPartSoal,
                        'petunjuk' => $petunjukPartSoal,
                    ],
                    'soal' => collect($soal)
                        ->values()
                        ->map(fn ($dataSoal, int $indeks): array => $this->petakanSoalPratinjau(
                            $dataSoal,
                            $nomorGlobal + $indeks,
                        ))
                        ->all(),
                ];

                $nomorGlobal += count($soal);
            }
        }

        $blocksCollection = collect($blocks);

        return [
            'sesiUjian' => [
                'id' => 0,
                'status' => 'preview',
                'pengguna' => ['name' => 'Pratinjau'],
            ],
            'overview' => [
                'judulUjian' => $ujianDenganRelasi->judul,
                'durasiMenit' => $ujianDenganRelasi->durasi_menit,
                'jumlahSoal' => $nomorGlobal - 1,
                'section' => $blocksCollection
                    ->groupBy('section.kode')
                    ->map(fn (SupportCollection $items): array => [
                        'kode' => $items->first()['section']['kode'],
                        'nama' => $items->first()['section']['nama'],
                        'materi' => $items->first()['materi'],
                        'jumlahSoal' => $items->sum('jumlahSoal'),
                        'partSoal' => $items
                            ->groupBy(fn (array $block): string => (string) ($block['partSoal']['id'] ?? $block['partSoal']['nama']))
                            ->map(fn (SupportCollection $part): array => [
                                'id' => $part->first()['partSoal']['id'] ?? null,
                                'nama' => $part->first()['partSoal']['nama'],
                                'jumlahSoal' => $part->sum('jumlahSoal'),
                            ])
                            ->values()
                            ->all(),
                    ])
                    ->values()
                    ->all(),
            ],
            'blocks' => $blocks,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function petakanSoalPratinjau(Soal $soal, int $nomorGlobal): array
    {
        $materiSoal = $soal->materiSoal;
        $urlAudio = $materiSoal?->url_audio;
        $urlGambar = $materiSoal?->url_gambar;

        return [
            'id' => $soal->id,
            'teks_soal' => $soal->teks_soal,
            'pilihan_a' => $soal->pilihan_a,
            'pilihan_b' => $soal->pilihan_b,
            'pilihan_c' => $soal->pilihan_c,
            'pilihan_d' => $soal->pilihan_d,
            'urutan' => $soal->urutan,
            'nomor_global' => $nomorGlobal,
            'materiSoal' => $materiSoal === null ? null : [
                'id' => $materiSoal->id,
                'judul' => $materiSoal->judul,
                'isi_teks' => $materiSoal->isi_teks,
                'url_audio' => $urlAudio,
                'url_gambar' => $urlGambar,
                'memiliki_audio' => $urlAudio !== null,
                'memiliki_gambar' => $urlGambar !== null,
            ],
            'materi' => $urlAudio !== null ? 'audio' : 'text',
        ];
    }

    public function dataTambah(): array
    {
        $bankDenganSection = [];

        foreach ($this->sectionRepo->semuaAktifBerurutan() as $section) {
            $bankDenganSection[] = [
                'section_id' => $section->id,
                'label' => $section->nama,
                'bankSoal' => $this->soalRepo->bankDenganSectionDanProgramTes($section->id, null),
            ];
        }

        return [
            'opsiSection' => $this->sectionRepo->semuaAktifBerurutan()->map(fn (Section $section) => ['value' => $section->id, 'label' => $section->nama, 'kode' => $section->kode])->toArray(),
            'partSoal' => $this->partSoalRepo->semuaAktifBerurutan(),
            'bankDenganSection' => $bankDenganSection,
        ];
    }

    public function bankSoalAktif(): Collection
    {
        return $this->bankSoalRepo->semuaAktifBerurutan();
    }

    public function lampirkanSoal(Ujian $ujian, PartUjian $part, Section $section): int
    {
        $soalTersedia = $this->soalRepo->soalDenganSectionUntukProgramTes($section->id, $ujian->program_tes_id, $part->bank_soal_id);

        $terpasang = array_fill_keys($this->partRepo->idSoalDalamPart($part->id), true);
        $nomor = $this->partRepo->urutanSoalBerikutnya($part->id);
        $dilampirkan = 0;

        foreach ($soalTersedia as $soal) {
            if (isset($terpasang[$soal->id])) {
                continue;
            }

            $this->partRepo->buatPivotSoal($part->id, $soal->id, $nomor++);
            $terpasang[$soal->id] = true;
            $dilampirkan++;
        }

        $this->partRepo->perbaruiJumlahSoal($part, $this->partRepo->jumlahSoalDalamPart($part->id));

        return $dilampirkan;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function bersihkanDeskripsi(array $data): array
    {
        if (! array_key_exists('deskripsi', $data)) {
            return $data;
        }

        $deskripsi = $data['deskripsi'];

        if (! is_string($deskripsi)) {
            $data['deskripsi'] = null;

            return $data;
        }

        $bersih = Purifier::clean($deskripsi);
        $teks = trim(html_entity_decode(strip_tags($bersih), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $data['deskripsi'] = $teks === '' ? null : $bersih;

        return $data;
    }

    private function pastikanDataPembuatanUjian(array $data): BankSoal
    {
        $programTes = $this->programTesRepo->find($data['program_tes_id']);
        $bank = $this->bankSoalRepo->find($data['bank_soal_id']);

        if ($programTes === null || ! $programTes->aktif) {
            throw ValidationException::withMessages([
                'program_tes_id' => 'Program tes tidak aktif atau tidak ditemukan.',
            ]);
        }

        if ($bank === null || ! $bank->aktif) {
            throw ValidationException::withMessages([
                'bank_soal_id' => 'Bank soal tidak aktif atau tidak ditemukan.',
            ]);
        }

        if ($bank->program_tes_id !== $programTes->id) {
            throw ValidationException::withMessages([
                'bank_soal_id' => 'Bank soal harus berasal dari program tes yang dipilih.',
            ]);
        }

        return $bank;
    }
}
