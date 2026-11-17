<?php

namespace App\Modules\Ujian\Services;

use App\Models\BankSoal;
use App\Models\MateriSoal;
use App\Models\PartSoal;
use App\Models\Section;
use App\Models\Soal;
use App\Modules\DataInduk\Repositories\Contracts\PartSoalRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\ProgramTesRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\SectionRepositoryInterface;
use App\Modules\Ujian\DTOs\KonfigurasiKebijakanMateriSoal;
use App\Modules\Ujian\Repositories\Contracts\BankSoalRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\MateriSoalRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\SoalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mews\Purifier\Facades\Purifier;

class LayananSoal
{
    private const SOAL_PER_HALAMAN_PUSTAKA = 200;

    public function __construct(
        private SoalRepositoryInterface $soalRepo,
        private BankSoalRepositoryInterface $bankSoalRepo,
        private MateriSoalRepositoryInterface $materiSoalRepo,
        private LayananMateriSoal $materiSoalService,
        private LayananKebijakanMateriSoal $kebijakanMateri,
        private SectionRepositoryInterface $sectionRepo,
        private PartSoalRepositoryInterface $partSoalRepo,
        private ProgramTesRepositoryInterface $programTesRepo,
    ) {}

    private function opsiSection(?string $programTesId = null): array
    {
        return $this->sectionRepo->semuaAktifBerurutan($programTesId)
            ->map(fn (Section $section) => [
                'value' => $section->id,
                'label' => $section->nama,
                'kode' => $section->kode,
                'deskripsi' => $section->deskripsi,
                'aktif' => $section->aktif,
            ])
            ->toArray();
    }

    public function dataTambah(?string $bankSoalTerpilih, array $filter): array
    {
        $bankSoal = $bankSoalTerpilih !== null ? $this->bankSoalRepo->find($bankSoalTerpilih) : null;

        return [
            'bankSoal' => $this->bankSoalRepo->semuaAktifBerurutan(),
            'opsiSection' => $this->opsiSection($bankSoal?->program_tes_id),
            'partSoal' => $this->partSoalRepo->semuaAktifBerurutan(),
            'materiSoal' => $bankSoal !== null
                ? $this->materiSoalRepo->semuaUntukBank($bankSoal->id)
                : new Collection,
            'kebijakanMateriSoal' => $this->kebijakanMateri->semuaUntukFrontend(),
            'bankSoalTerpilih' => $bankSoalTerpilih !== null && $bankSoalTerpilih !== '' && $this->bankSoalRepo->adaAktif($bankSoalTerpilih)
                ? $bankSoalTerpilih
                : null,
            'filterKembali' => $filter,
        ];
    }

    public function dataUbah(Soal $soal): array
    {
        $soalDenganRelasi = $this->soalRepo->temukanDenganRelasi($soal->id);
        $materiSoalAktif = $soalDenganRelasi?->materiSoal;

        return [
            'soal' => $soalDenganRelasi,
            'bankSoal' => $this->bankSoalRepo->semuaAktifBerurutan(),
            'opsiSection' => $this->opsiSection($soalDenganRelasi?->bankSoal?->program_tes_id),
            'partSoal' => $this->partSoalRepo->semuaAktifBerurutan(),
            'materiSoal' => $materiSoalAktif !== null ? [$materiSoalAktif] : [],
            'kebijakanMateriSoal' => $this->kebijakanMateri->semuaUntukFrontend(),
        ];
    }

    public function dataDaftar(array $filter): array
    {
        $bankSoalId = $filter['bank_soal_id'] ?? null;
        $partSoal = $this->partSoalRepo->semuaAktifBerurutan();

        if (empty($bankSoalId) && ! empty($filter['part_soal_id'])) {
            $partSoalTerpilih = $partSoal->first(
                fn (PartSoal $part): bool => $part->id === $filter['part_soal_id'],
            );
            $bankSoalId = $partSoalTerpilih?->bank_soal_id;
        }

        $bankSoal = $bankSoalId !== null ? $this->bankSoalRepo->find($bankSoalId) : null;
        $bankTerkunci = $bankSoal !== null && $this->bankSoalRepo->digunakanDalamUjian($bankSoal->id);
        $programTesId = $filter['program_tes_id'] ?? null;
        if ($bankSoal !== null) {
            $programTesId = $bankSoal->program_tes_id;
        }
        $opsiSection = $this->opsiSection($bankSoal?->program_tes_id);
        $normalizedFilter = $filter;

        if ($bankSoalId !== null) {
            $normalizedFilter['bank_soal_id'] = $bankSoalId;
        }

        if ($bankSoal !== null) {
            $selectedPart = ! empty($filter['part_soal_id'])
                ? $partSoal->first(
                    fn (PartSoal $part): bool => $part->id === $filter['part_soal_id']
                        && $part->bank_soal_id === $bankSoal->id,
                )
                : null;

            $sectionId = $filter['section'] ?? null;
            if ($sectionId === null && $selectedPart !== null) {
                $sectionId = $selectedPart->section_id;
            }

            $sectionIds = collect($opsiSection)
                ->pluck('value')
                ->map(static fn ($id): string => (string) $id);

            if ($sectionId !== null && ! $sectionIds->contains((string) $sectionId)) {
                $sectionId = null;
            }

            if ($sectionId === null) {
                $sectionId = $opsiSection[0]['value'] ?? null;
            }

            if ($sectionId !== null) {
                $normalizedFilter['section'] = $sectionId;
            } else {
                unset($normalizedFilter['section'], $normalizedFilter['part_soal_id']);
            }

            $selectedPart = ! empty($normalizedFilter['part_soal_id'])
                ? $partSoal->first(
                    fn (PartSoal $part): bool => $part->id === $normalizedFilter['part_soal_id']
                        && $part->bank_soal_id === $bankSoal->id
                        && $part->section_id === $sectionId,
                )
                : null;

            if ($selectedPart === null) {
                $firstPart = $partSoal->first(
                    fn (PartSoal $part): bool => $part->bank_soal_id === $bankSoal->id
                        && $part->section_id === $sectionId,
                );

                if ($firstPart !== null) {
                    $normalizedFilter['part_soal_id'] = $firstPart->id;
                } else {
                    unset($normalizedFilter['part_soal_id']);
                }
            }
        }

        $soal = $bankSoalId
            ? $this->soalRepo->paginateDenganFilter($normalizedFilter, self::SOAL_PER_HALAMAN_PUSTAKA)
            : $this->soalRepo->paginatorKosong(self::SOAL_PER_HALAMAN_PUSTAKA);

        return [
            'soal' => $soal,
            'programTes' => $this->programTesRepo->semuaAktifBerurutan(),
            'bankSoal' => $this->bankSoalRepo->semuaAktifDenganProgramTesDanJumlah($programTesId),
            'bankSoalTerpilih' => $bankSoal,
            'bankTerkunci' => $bankTerkunci,
            'opsiSection' => $opsiSection,
            'partSoal' => $partSoal,
            'kebijakanMateriSoal' => $this->kebijakanMateri->semuaUntukFrontend(),
            'filter' => $normalizedFilter,
            'bankSoalTerpilihId' => $bankSoalId,
            'programTesTerpilihId' => $programTesId,
        ];
    }

    public function simpanPustaka(array $validated, ?Request $request): int
    {
        $storedFiles = [];

        try {
            $count = DB::transaction(function () use ($validated, $request, &$storedFiles) {
                $bankSoalId = $validated['bank_soal_id'];
                $this->pastikanBankBelumDipakai($bankSoalId);
                $materiId = $validated['materi_soal_id'] ?? null;

                if (! $materiId && ! empty($validated['materi_baru_judul'])) {
                    $materi = $this->buatMateriDariRequest($validated, $request, $storedFiles);
                    $materiId = $materi->id;
                }

                $materi = $materiId ? $this->materiSoalRepo->find($materiId) : null;

                if ($materi !== null) {
                    $this->pastikanMateriMilikBank($materi, $bankSoalId);
                }

                $urutan = $materiId ? $this->soalRepo->urutanMaksimumMateriSoal($materiId) + 1 : 1;

                $konfigurasiKebijakan = [];

                $baris = [];
                foreach ($validated['soal'] as $dataSoal) {
                    $sectionId = $dataSoal['section_id'];
                    $konfigurasi = $konfigurasiKebijakan[$sectionId] ??= $this->selesaikanKebijakanAtauGagal($sectionId, 'soal');

                    $this->pastikanKonteksSoal(
                        $bankSoalId,
                        $sectionId,
                        $dataSoal['part_soal_id'],
                        'soal',
                    );
                    $this->kebijakanMateri->pastikanMateriDiizinkan($konfigurasi, $materi, 'soal');

                    if ($konfigurasi->materi() === 'text') {
                        $this->pastikanIntegritasTeks($dataSoal, 'soal');
                    }

                    $baris[] = [
                        'bank_soal_id' => $bankSoalId,
                        'section_id' => $sectionId,
                        'part_soal_id' => $dataSoal['part_soal_id'],
                        'materi_soal_id' => $materiId,
                        'teks_soal' => $dataSoal['teks_soal'] ?? '',
                        'pilihan_a' => $dataSoal['pilihan_a'] ?? '',
                        'pilihan_b' => $dataSoal['pilihan_b'] ?? '',
                        'pilihan_c' => $dataSoal['pilihan_c'] ?? '',
                        'pilihan_d' => $dataSoal['pilihan_d'] ?? '',
                        'kunci_jawaban' => $dataSoal['kunci_jawaban'],
                        'urutan' => $urutan++,
                        'pembuat_id' => auth()->id(),
                    ];
                }

                $this->pastikanMateriSatuGrup($baris);

                $this->soalRepo->buatBanyak($baris);

                return count($baris);
            });
        } catch (\Throwable $e) {
            foreach ($storedFiles as $file) {
                Storage::disk('public')->delete($file);
            }

            throw $e;
        }

        return $count;
    }

    /**
     * @return array<string, mixed>
     */
    public function dataPratinjauBank(
        string $bankSoalId,
        ?string $programTesId,
        string $participantName,
        ?string $initialSoalId = null,
    ): array {
        $bankSoal = $this->bankSoalRepo->find($bankSoalId);

        if ($bankSoal === null || ! $bankSoal->aktif) {
            throw (new ModelNotFoundException)->setModel(BankSoal::class, [$bankSoalId]);
        }

        if ($programTesId !== null && $bankSoal->program_tes_id !== $programTesId) {
            throw (new ModelNotFoundException)->setModel(BankSoal::class, [$bankSoalId]);
        }

        $section = $this->sectionRepo->semuaAktifBerurutan($bankSoal->program_tes_id);
        $partSoal = $this->partSoalRepo->semuaAktifDenganBank($bankSoal->id);
        $soal = $this->soalRepo->semuaUntukPratinjauBank($bankSoal->id);

        if ($initialSoalId !== null && ! $soal->contains('id', $initialSoalId)) {
            throw (new ModelNotFoundException)->setModel(Soal::class, [$initialSoalId]);
        }

        $soalPerSection = $soal->groupBy('section_id');
        $materiPerSection = $section->mapWithKeys(fn (Section $section): array => [
            $section->id => $this->kebijakanMateri->materiUntuk($section->id),
        ]);

        $blocks = [];
        $nomorGlobal = 1;

        foreach ($section as $section) {
            $soalSection = $soalPerSection->get($section->id, new Collection);
            $partsSection = $partSoal
                ->where('section_id', $section->id)
                ->sortBy('urutan')
                ->values();
            $materi = $materiPerSection->get($section->id)
                ?? ($soalSection->first()?->materiSoal?->url_audio !== null ? 'audio' : 'text');

            foreach ($partsSection as $part) {
                $soalPart = $soalSection
                    ->where('part_soal_id', $part->id)
                    ->sortBy('urutan')
                    ->values();

                if ($soalPart->isEmpty()) {
                    continue;
                }

                $blocks[] = $this->buatBlokPratinjau(
                    $section,
                    $part->nama,
                    $part->petunjuk,
                    $soalPart,
                    $materi,
                    $nomorGlobal,
                );
                $nomorGlobal += $soalPart->count();
            }

            $soalStandalone = $soalSection
                ->whereNull('part_soal_id')
                ->sortBy('urutan')
                ->values();

            if ($soalStandalone->isNotEmpty()) {
                $blocks[] = $this->buatBlokPratinjau(
                    $section,
                    'Soal Standalone',
                    null,
                    $soalStandalone,
                    $materi,
                    $nomorGlobal,
                );
                $nomorGlobal += $soalStandalone->count();
            }
        }

        $blocks = collect($blocks);

        return [
            'sesiUjian' => [
                'id' => 0,
                'status' => 'preview',
                'pengguna' => ['name' => $participantName],
            ],
            'overview' => [
                'judulUjian' => $bankSoal->nama,
                'durasiMenit' => null,
                'jumlahSoal' => $nomorGlobal - 1,
                'section' => $blocks
                    ->groupBy('section.kode')
                    ->map(fn (SupportCollection $items): array => [
                        'kode' => $items->first()['section']['kode'],
                        'nama' => $items->first()['section']['nama'],
                        'materi' => $items->first()['materi'],
                        'jumlahSoal' => $items->sum('jumlahSoal'),
                        'partSoal' => $items->map(fn (array $block): array => [
                            'nama' => $block['partSoal']['nama'],
                            'jumlahSoal' => $block['jumlahSoal'],
                        ])->values()->all(),
                    ])
                    ->values()
                    ->all(),
            ],
            'blocks' => $blocks->values()->all(),
            'initialSoalId' => $initialSoalId,
        ];
    }

    /**
     * @param  Collection<int, Soal>  $soal
     * @return array<string, mixed>
     */
    private function buatBlokPratinjau(
        Section $section,
        string $namaPart,
        ?string $petunjuk,
        Collection $soal,
        string $materi,
        int $nomorMulai,
    ): array {
        return [
            'nomorMulai' => $nomorMulai,
            'jumlahSoal' => $soal->count(),
            'materi' => $materi,
            'section' => [
                'kode' => $section->kode,
                'nama' => $section->nama,
                'deskripsi' => $section->deskripsi,
            ],
            'partSoal' => [
                'nama' => $namaPart,
                'petunjuk' => $petunjuk,
            ],
            'soal' => $soal->values()->map(
                fn (Soal $dataSoal, int $indeks): array => $this->petakanSoalPratinjau(
                    $dataSoal,
                    $materi,
                    $nomorMulai + $indeks,
                ),
            )->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function petakanSoalPratinjau(Soal $soal, string $materi, int $nomorGlobal): array
    {
        return [
            'id' => $soal->id,
            'teks_soal' => $soal->teks_soal,
            'pilihan_a' => $soal->pilihan_a,
            'pilihan_b' => $soal->pilihan_b,
            'pilihan_c' => $soal->pilihan_c,
            'pilihan_d' => $soal->pilihan_d,
            'urutan' => $soal->urutan,
            'nomor_global' => $nomorGlobal,
            'materiSoal' => $soal->materiSoal,
            'materi' => $materi,
        ];
    }

    public function perbaruiPustaka(Soal $soal, array $validated): void
    {
        $this->pastikanSoalBelumDikunci($soal, $validated['bank_soal_id']);
        $this->pastikanKonteksSoal(
            $validated['bank_soal_id'],
            $validated['section_id'],
            $validated['part_soal_id'],
        );
        $this->pastikanMateriTidakBerubah($soal, $validated);

        $konfigurasi = $this->selesaikanKebijakanAtauGagal($validated['section_id'], 'section_id');

        $materi = ! empty($validated['materi_soal_id'])
            ? $this->materiSoalRepo->find($validated['materi_soal_id'])
            : null;

        if ($materi !== null) {
            $this->pastikanMateriMilikBank($materi, $validated['bank_soal_id']);
        }

        $this->kebijakanMateri->pastikanMateriDiizinkan($konfigurasi, $materi, 'materi_soal_id');
        $this->pastikanMateriSatuGrup([$validated], $soal->id);

        if ($konfigurasi->materi() === 'text') {
            $this->pastikanIntegritasTeks($validated, 'teks_soal');
        }

        $data = $validated;
        $data['pengubah_id'] = auth()->id();
        $this->soalRepo->update($soal, $data);
    }

    public function hapus(Soal $soal): void
    {
        $this->pastikanSoalBelumDikunci($soal);

        $this->soalRepo->delete($soal);
    }

    public function simpan(array $validated): void
    {
        DB::transaction(function () use ($validated): void {
            $this->pastikanBankBelumDipakai($validated['bank_soal_id']);
            $this->pastikanKonteksSoal(
                $validated['bank_soal_id'],
                $validated['section_id'],
                $validated['part_soal_id'],
            );
            $this->pastikanKebijakanMateriSoal($validated);
            $this->pastikanMateriSatuGrup([$validated]);

            $this->soalRepo->create(array_merge(
                $validated,
                ['pembuat_id' => auth()->id()]
            ));
        });
    }

    public function perbarui(Soal $soal, array $validated): void
    {
        $this->pastikanSoalBelumDikunci($soal, $validated['bank_soal_id']);
        $this->pastikanKonteksSoal(
            $validated['bank_soal_id'],
            $validated['section_id'],
            $validated['part_soal_id'],
        );
        $this->pastikanMateriTidakBerubah($soal, $validated);
        $this->pastikanKebijakanMateriSoal($validated);
        $this->pastikanMateriSatuGrup([$validated], $soal->id);

        $this->soalRepo->update($soal, array_merge(
            $validated,
            ['pengubah_id' => auth()->id()]
        ));
    }

    public function memilikiDuplikatDalam(array $items, callable $ambilKunci): bool
    {
        $keys = array_map($ambilKunci, $items);

        return count($keys) !== count(array_unique($keys));
    }

    private function buatMateriDariRequest(array $validated, ?Request $request, array &$berkasTersimpan): MateriSoal
    {
        $materi = $this->materiSoalRepo->create([
            'bank_soal_id' => $validated['bank_soal_id'],
            'judul' => $validated['materi_baru_judul'],
            'isi_teks' => ! empty($validated['materi_baru_isi_teks'])
                ? Purifier::clean($validated['materi_baru_isi_teks'])
                : null,
        ]);

        $berkasTersimpan = array_merge(
            $berkasTersimpan,
            $this->materiSoalService->lampirkanMediaUnggahan(
                $materi,
                $request,
                'materi_baru_berkas_audio',
                'materi_baru_berkas_gambar',
            ),
        );

        return $materi;
    }

    private function pastikanKonteksSoal(
        string $bankSoalId,
        string $sectionId,
        string $partSoalId,
        string $kunciKesalahan = 'part_soal_id',
    ): void {
        $bankSoal = $this->bankSoalRepo->find($bankSoalId);
        if ($bankSoal === null || ! $bankSoal->aktif) {
            throw ValidationException::withMessages([
                'bank_soal_id' => 'Bank soal tidak aktif atau tidak ditemukan.',
            ]);
        }

        $section = $this->sectionRepo->find($sectionId);
        if ($section === null || ! $section->aktif) {
            throw ValidationException::withMessages([
                'section_id' => 'Section tidak aktif atau tidak ditemukan.',
            ]);
        }

        if ($section->program_tes_id !== $bankSoal->program_tes_id) {
            throw ValidationException::withMessages([
                'section_id' => 'Section harus berasal dari program tes yang sama dengan bank soal.',
            ]);
        }

        $partSoal = $this->partSoalRepo->find($partSoalId);

        if ($partSoal === null
            || ! $partSoal->aktif
            || $partSoal->section_id !== $sectionId
            || $partSoal->bank_soal_id !== $bankSoalId) {
            throw ValidationException::withMessages([
                $kunciKesalahan => 'Part soal harus aktif dan milik bank soal serta section yang sama.',
            ]);
        }
    }

    private function pastikanBankBelumDipakai(string $bankSoalId): void
    {
        if ($this->bankSoalRepo->digunakanDalamUjian($bankSoalId)) {
            throw ValidationException::withMessages([
                'bank_soal_id' => 'Soal tidak bisa ditambahkan karena bank soal sudah digunakan di ujian.',
            ]);
        }
    }

    private function pastikanSoalBelumDikunci(Soal $soal, ?string $bankSoalId = null): void
    {
        $bankIds = array_unique(array_filter([
            $soal->bank_soal_id,
            $bankSoalId,
        ]));

        foreach ($bankIds as $id) {
            if ($this->bankSoalRepo->digunakanDalamUjian($id)) {
                throw ValidationException::withMessages([
                    'bank_soal_id' => 'Soal tidak bisa diubah atau dihapus karena bank soal sudah digunakan di ujian.',
                ]);
            }
        }

        if ($this->soalRepo->digunakanDalamUjian($soal->id)) {
            throw ValidationException::withMessages([
                'soal' => 'Soal tidak bisa diubah atau dihapus karena sudah digunakan di ujian.',
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $baris
     */
    private function pastikanMateriSatuGrup(array $baris, ?string $kecualiSoalId = null): void
    {
        $grupBaruPerMateri = [];

        foreach ($baris as $barisSoal) {
            if (empty($barisSoal['materi_soal_id'])) {
                continue;
            }

            $materiId = (string) $barisSoal['materi_soal_id'];
            $grupBaruPerMateri[$materiId][$this->kunciGrupMateri($barisSoal)] = true;
        }

        if ($grupBaruPerMateri === []) {
            return;
        }

        $grupLamaPerMateri = $this->soalRepo
            ->grupMateriSoal(array_keys($grupBaruPerMateri), $kecualiSoalId)
            ->groupBy('materi_soal_id');

        foreach ($grupBaruPerMateri as $materiId => $grupBaru) {
            if (count($grupBaru) > 1) {
                throw ValidationException::withMessages([
                    'materi_soal_id' => 'Materi soal hanya dapat digunakan pada satu group section dan part soal.',
                ]);
            }

            $grupBaruId = array_key_first($grupBaru);

            foreach ($grupLamaPerMateri->get($materiId, new Collection) as $grupLama) {
                if ($this->kunciGrupMateri([
                    'bank_soal_id' => $grupLama->bank_soal_id,
                    'section_id' => $grupLama->section_id,
                    'part_soal_id' => $grupLama->part_soal_id,
                ]) !== $grupBaruId) {
                    throw ValidationException::withMessages([
                        'materi_soal_id' => 'Materi soal sudah digunakan pada group section atau part soal lain.',
                    ]);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function pastikanMateriTidakBerubah(Soal $soal, array $validated): void
    {
        if (($validated['materi_soal_id'] ?? null) === $soal->materi_soal_id) {
            return;
        }

        throw ValidationException::withMessages([
            'materi_soal_id' => 'Materi soal tidak dapat diubah setelah soal dibuat.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $barisSoal
     */
    private function kunciGrupMateri(array $barisSoal): string
    {
        return implode('|', [
            (string) ($barisSoal['bank_soal_id'] ?? ''),
            (string) ($barisSoal['section_id'] ?? ''),
            (string) ($barisSoal['part_soal_id'] ?? ''),
        ]);
    }

    private function selesaikanKebijakanAtauGagal(string $sectionId, string $kunciKesalahan): KonfigurasiKebijakanMateriSoal
    {
        try {
            return $this->kebijakanMateri->selesaikanAtauGagal($sectionId);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages([$kunciKesalahan => $e->getMessage()]);
        }
    }

    private function pastikanMateriMilikBank(MateriSoal $materi, string $bankSoalId): void
    {
        if ($materi->bank_soal_id !== $bankSoalId) {
            throw ValidationException::withMessages(['materi_soal_id' => 'Materi soal harus berasal dari bank soal yang sama.']);
        }
    }

    private function pastikanKebijakanMateriSoal(array $validated): void
    {
        $konfigurasi = $this->selesaikanKebijakanAtauGagal($validated['section_id'], 'materi_soal_id');

        $materi = ! empty($validated['materi_soal_id'])
            ? $this->materiSoalRepo->find($validated['materi_soal_id'])
            : null;

        if ($materi !== null) {
            $this->pastikanMateriMilikBank($materi, $validated['bank_soal_id']);
        }

        $this->kebijakanMateri->pastikanMateriDiizinkan($konfigurasi, $materi, 'materi_soal_id');

        if ($konfigurasi->materi() === 'text') {
            $this->pastikanIntegritasTeks($validated, 'teks_soal');
        }
    }

    private function pastikanIntegritasTeks(array $dataSoal, string $kunciKesalahan): void
    {
        $pilihanKosong = collect(['pilihan_a', 'pilihan_b', 'pilihan_c', 'pilihan_d'])
            ->contains(fn ($kolom) => empty(trim($dataSoal[$kolom] ?? '')));

        if (empty(trim($dataSoal['teks_soal'] ?? '')) || $pilihanKosong) {
            throw ValidationException::withMessages([$kunciKesalahan => 'Soal tipe teks wajib memiliki teks soal dan seluruh pilihan jawaban.']);
        }
    }
}
