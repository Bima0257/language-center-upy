<?php

namespace App\Modules\DataInduk\Services;

use App\Models\Fakultas;
use App\Models\InterpretasiSkor;
use App\Models\PartSoal;
use App\Models\ProgramStudi;
use App\Models\ProgramTes;
use App\Models\Section;
use App\Models\Sertifikat;
use App\Modules\DataInduk\Repositories\Contracts\FakultasRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\InterpretasiSkorRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\PartSoalRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\ProgramStudiRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\ProgramTesRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\SectionRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\SertifikatRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\UrutanKodeRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\BankSoalRepositoryInterface;
use App\Modules\Ujian\Services\LayananKebijakanMateriSoal;
use App\Modules\Ujian\Services\LayananUjian;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mews\Purifier\Facades\Purifier;

class LayananDataInduk
{
    public function __construct(
        private ProgramTesRepositoryInterface $programTesRepo,
        private SectionRepositoryInterface $sectionRepo,
        private PartSoalRepositoryInterface $partSoalRepo,
        private FakultasRepositoryInterface $fakultasRepo,
        private ProgramStudiRepositoryInterface $programStudiRepo,
        private InterpretasiSkorRepositoryInterface $interpretasiSkorRepo,
        private SertifikatRepositoryInterface $sertifikatRepo,
        private UrutanKodeRepositoryInterface $urutanKodeRepo,
        private BankSoalRepositoryInterface $bankSoalRepo,
        private LayananKebijakanMateriSoal $kebijakanMateri,
        private LayananUjian $layananUjian,
    ) {}

    // ===== Sections =====
    public function dataDaftarSection(): array
    {
        return [
            'programTes' => $this->programTesRepo->semuaDenganJumlahBankSoal(),
        ];
    }

    public function dataDaftarSectionPerProgramTes(ProgramTes $programTes): array
    {
        return [
            'programTes' => $programTes,
            'section' => $this->sectionRepo->semuaBerurutan($programTes->id),
            'kebijakanMateriSoal' => $this->kebijakanMateri->semuaUntukFrontend(),
        ];
    }

    public function buatSection(array $data): Section
    {
        return DB::transaction(function () use ($data): Section {
            $data = $this->bersihkanTeksOpsional($data);
            $bankSoalKonteksId = $data['bank_soal_konteks_id'] ?? null;
            unset($data['bank_soal_konteks_id']);
            $programTes = $this->programTesRepo->find($data['program_tes_id']);

            if ($programTes === null || ! $programTes->aktif) {
                throw new \RuntimeException('Program tes tidak aktif atau tidak ditemukan.');
            }

            $this->pastikanBankSoalKonteks($bankSoalKonteksId, null, $programTes->id);

            $ruangKode = 'section:'.$programTes->kode;
            $nomorKode = $this->urutanKodeRepo->nilaiBerikutnya($ruangKode);
            $data['kode'] = $programTes->kode.str_pad((string) $nomorKode, 3, '0', STR_PAD_LEFT);
            $data['urutan'] = $data['urutan'] ?? $this->sectionRepo->urutanBerikutnya($programTes->id);

            $dataSection = $data;
            unset($dataSection['ketentuan_materi'], $dataSection['aturan_media']);

            $section = $this->sectionRepo->create($dataSection);
            $this->simpanKebijakanJikaDikirim($section, $data);

            if (auth()->check()) {
                activity()
                    ->performedOn($section)
                    ->withProperties([
                        'program_tes_id' => $section->program_tes_id,
                        'kode' => $section->kode,
                        'nama' => $section->nama,
                        'policy_configured' => $section->kebijakanMateriSoal()->exists(),
                    ])
                    ->log('section_created');
            }

            return $section;
        });
    }

    public function perbaruiSection(Section $section, array $data): void
    {
        $data = $this->bersihkanTeksOpsional($data);

        DB::transaction(function () use ($section, $data): void {
            $bankSoalKonteksId = $data['bank_soal_konteks_id'] ?? null;
            unset($data['bank_soal_konteks_id']);
            $this->pastikanBankSoalKonteks($bankSoalKonteksId, null, $section->program_tes_id);

            $dataSection = $data;
            unset($dataSection['ketentuan_materi'], $dataSection['aturan_media']);

            $this->sectionRepo->update($section, $dataSection);
            $this->simpanKebijakanJikaDikirim($section, $data);

            if (auth()->check()) {
                activity()
                    ->performedOn($section)
                    ->withProperties([
                        'changed_fields' => array_keys($data),
                    ])
                    ->log('section_updated');
            }
        });
    }

    public function hapusSection(Section $section, ?string $bankSoalKonteksId = null): void
    {
        DB::transaction(function () use ($section, $bankSoalKonteksId): void {
            $this->pastikanBankSoalKonteks($bankSoalKonteksId, null, $section->program_tes_id);

            $memilikiAturanPenilaian = $section->programTes()
                ->whereHas('aturanPenilaian', fn ($query) => $query->where('section_id', $section->id))
                ->exists();

            if ($section->partSoal()->exists()
                || $section->soal()->exists()
                || $section->partUjian()->exists()
                || $memilikiAturanPenilaian) {
                throw new \RuntimeException('Section tidak bisa dihapus karena masih digunakan di part soal, soal, atau section ujian.');
            }

            $this->kebijakanMateri->hapusUntukSection($section->id);

            if (auth()->check()) {
                activity()
                    ->performedOn($section)
                    ->withProperties([
                        'program_tes_id' => $section->program_tes_id,
                        'kode' => $section->kode,
                        'nama' => $section->nama,
                    ])
                    ->log('section_deleted');
            }

            $this->sectionRepo->delete($section);
        });
    }

    /**
     * Terima daftar id section dalam urutan baru; order dihitung ulang berdasarkan posisi.
     *
     * @param  array<string>  $ids
     */
    public function susunUlangSection(array $ids, string $programTesId): void
    {
        DB::transaction(function () use ($ids, $programTesId): void {
            $ids = array_values($ids);
            $section = $this->sectionRepo->semuaBerurutan($programTesId);
            $idsDiminta = collect($ids)->sort()->values()->all();
            $idsTersedia = $section->pluck('id')->sort()->values()->all();

            if ($idsDiminta !== $idsTersedia) {
                throw ValidationException::withMessages([
                    'section' => 'Daftar section harus lengkap dan berasal dari program tes yang dipilih.',
                ]);
            }

            $sectionById = $section->keyBy('id');

            // Clear current positions first so swapping two positions cannot hit the unique index.
            foreach ($ids as $index => $id) {
                $this->sectionRepo->update($sectionById[$id], ['urutan' => -($index + 1)]);
            }

            foreach ($ids as $index => $id) {
                $this->sectionRepo->update($sectionById[$id], ['urutan' => $index + 1]);
            }

            $programTes = $this->programTesRepo->find($programTesId);
            if ($programTes !== null && auth()->check()) {
                activity()
                    ->performedOn($programTes)
                    ->withProperties([
                        'section' => $ids,
                    ])
                    ->log('section_reordered');
            }
        });
    }

    // ===== Exam Types =====
    public function dataDaftarProgramTes(): array
    {
        return [
            'programTes' => $this->programTesRepo->semuaDenganJumlahBankSoal(),
        ];
    }

    public function buatProgramTes(array $data): ProgramTes
    {
        return DB::transaction(function () use ($data): ProgramTes {
            $data = $this->bersihkanTeksOpsional($data);
            $data['kode'] = str_pad((string) $this->urutanKodeRepo->nilaiBerikutnya('program_tes'), 3, '0', STR_PAD_LEFT);
            $programTes = $this->programTesRepo->create($data);

            $this->urutanKodeRepo->pastikanRuang('section:'.$programTes->kode);

            return $programTes;
        });
    }

    public function perbaruiProgramTes(ProgramTes $programTes, array $data): void
    {
        $this->programTesRepo->update($programTes, $this->bersihkanTeksOpsional($data));
    }

    public function hapusProgramTes(ProgramTes $programTes): void
    {
        if ($programTes->bankSoal()->exists()
            || $programTes->section()->exists()
            || $programTes->ujian()->exists()
            || $programTes->aturanPenilaian()->exists()
            || $programTes->interpretasiSkor()->exists()
            || $programTes->skemaPenilaian()->exists()) {
            throw new \RuntimeException('Program tes tidak bisa dihapus karena masih digunakan konfigurasi ujian.');
        }

        $this->programTesRepo->delete($programTes);
    }

    public function dataDaftarPartSoal(?string $bankSoalId = null, ?string $programTesId = null): array
    {
        $partSoal = $this->partSoalRepo->semuaDenganJumlahSoal();
        $partSoalDalamUjianAktif = array_fill_keys(
            $this->layananUjian->idPartSoalDalamUjianAktif($partSoal->pluck('id')->all()),
            true,
        );
        $partSoal->each(fn (PartSoal $part): mixed => $part->setAttribute(
            'dipakai_ujian_aktif',
            isset($partSoalDalamUjianAktif[$part->id]),
        ));
        $bankSoalTerpilih = $bankSoalId !== null ? $this->bankSoalRepo->find($bankSoalId) : null;
        $programTesId = $bankSoalTerpilih !== null ? $bankSoalTerpilih->program_tes_id : $programTesId;
        $bankSoal = $this->bankSoalRepo->semuaAktifDenganProgramTesBerurutan($programTesId);

        if ($bankSoalId !== null) {
            $partSoal = $partSoal->where('bank_soal_id', $bankSoalId)->values();
        } elseif ($programTesId !== null) {
            $partSoal = $partSoal->whereIn('bank_soal_id', $bankSoal->pluck('id'))->values();
        }

        return [
            'partSoal' => $partSoal,
            'bankSoal' => $bankSoal,
            'programTes' => $this->programTesRepo->semuaAktifBerurutan(),
            'bankSoalTerpilihId' => $bankSoalId,
            'programTesTerpilihId' => $programTesId,
            'opsiSection' => $this->sectionRepo->semuaAktifBerurutan($programTesId)->map(fn (Section $section) => ['value' => $section->id, 'label' => $section->nama, 'kode' => $section->kode])->toArray(),
        ];
    }

    public function buatPartSoal(array $data): void
    {
        DB::transaction(function () use ($data): void {
            $data = $this->bersihkanTeksOpsional($data);
            $bankSoalKonteksId = $data['bank_soal_konteks_id'] ?? null;
            unset($data['bank_soal_konteks_id']);
            $this->pastikanBankSoalKonteks($bankSoalKonteksId, $data['bank_soal_id']);
            $this->pastikanSectionMilikBank($data['bank_soal_id'], $data['section_id']);
            $data['urutan'] = $this->partSoalRepo->urutanBerikutnya($data['bank_soal_id'], $data['section_id']);
            $data['aktif'] = $data['aktif'] ?? true;

            $partSoal = $this->partSoalRepo->create($data);
            $this->layananUjian->sinkronkanPartSoalGlobal(new Collection([$partSoal]));
        });
    }

    public function perbaruiPartSoal(PartSoal $partSoal, array $data): void
    {
        $data = $this->bersihkanTeksOpsional($data);
        $bankSoalKonteksId = $data['bank_soal_konteks_id'] ?? null;
        unset($data['bank_soal_konteks_id']);

        DB::transaction(function () use ($partSoal, $data, $bankSoalKonteksId): void {
            $partSoalSaatIni = $this->partSoalRepo->find($partSoal->id) ?? $partSoal;
            $partSoalSaatIni->fill($data);
            $kolomBerubah = array_keys($partSoalSaatIni->getDirty());

            if ($kolomBerubah === []) {
                return;
            }

            $bankSoalId = $data['bank_soal_id'];
            $sectionId = $data['section_id'];
            $this->pastikanBankSoalKonteks($bankSoalKonteksId, $bankSoalId);
            $this->pastikanSectionMilikBank($bankSoalId, $sectionId);

            $partSoalDipakaiUjianAktif = $this->layananUjian
                ->idPartSoalDalamUjianAktif([$partSoalSaatIni->id]) !== [];
            $kolomDilindungi = ['bank_soal_id', 'section_id', 'nama', 'petunjuk', 'aktif'];

            if ($partSoalDipakaiUjianAktif && array_intersect($kolomBerubah, $kolomDilindungi) !== []) {
                throw ValidationException::withMessages([
                    'part_soal' => 'Part soal tidak dapat mengubah metadata karena sedang dipakai ujian aktif.',
                ]);
            }

            $pindahGrup = $bankSoalId !== $partSoalSaatIni->bank_soal_id
                || $sectionId !== $partSoalSaatIni->section_id;

            if ($pindahGrup && $partSoalSaatIni->soal()->exists()) {
                throw ValidationException::withMessages([
                    'part_soal' => 'Part soal yang sudah memiliki soal tidak dapat dipindahkan bank atau sectionnya.',
                ]);
            }

            // Pindah bank/section menempatkan part di akhir grup tujuan.
            if ($pindahGrup) {
                $data['urutan'] = $this->partSoalRepo->urutanBerikutnya($bankSoalId, $sectionId);
            }

            $partSoalDiperbarui = $this->partSoalRepo->update($partSoalSaatIni, $data);
            $this->layananUjian->sinkronkanPartSoalGlobal(new Collection([$partSoalDiperbarui]));
        });
    }

    /**
     * Terima daftar id part dalam urutan baru; order dihitung ulang per (bank, section).
     *
     * @param  array<string>  $ids
     */
    public function susunUlangPartSoal(array $ids): void
    {
        DB::transaction(function () use ($ids): void {
            $ids = array_values($ids);
            $partSoal = $this->partSoalRepo->temukanBanyak($ids);

            if (count($ids) !== $partSoal->count()) {
                throw ValidationException::withMessages([
                    'part_soal' => 'Daftar part soal mengandung data yang tidak ditemukan.',
                ]);
            }

            $kelompok = $partSoal
                ->groupBy(fn (PartSoal $part): string => $part->bank_soal_id.'|'.$part->section_id);

            if ($kelompok->count() !== 1) {
                throw ValidationException::withMessages([
                    'part_soal' => 'Urutan hanya dapat diubah untuk satu grup bank soal dan section.',
                ]);
            }

            $partGrup = $kelompok->first();
            $partPertama = $partGrup->first();
            $idsTersedia = $this->partSoalRepo->idBerurutanDenganBankDanSection(
                $partPertama->bank_soal_id,
                $partPertama->section_id,
            );

            if (collect($ids)->sort()->values()->all() !== collect($idsTersedia)->sort()->values()->all()) {
                throw ValidationException::withMessages([
                    'part_soal' => 'Daftar part soal harus lengkap untuk grup yang dipilih.',
                ]);
            }

            $partSoalById = $partSoal->keyBy('id');
            foreach ($ids as $index => $id) {
                $this->partSoalRepo->update($partSoalById[$id], ['urutan' => -($index + 1)]);
            }

            $partSoalDiperbarui = new Collection;
            foreach ($ids as $index => $id) {
                $partSoalDiperbarui->push(
                    $this->partSoalRepo->update($partSoalById[$id], ['urutan' => $index + 1]),
                );
            }

            $this->layananUjian->sinkronkanPartSoalGlobal($partSoalDiperbarui);
        });
    }

    public function hapusPartSoal(PartSoal $partSoal, ?string $bankSoalKonteksId = null): void
    {
        DB::transaction(function () use ($partSoal, $bankSoalKonteksId): void {
            $partSoalSaatIni = $this->partSoalRepo->find($partSoal->id) ?? $partSoal;
            $this->pastikanBankSoalKonteks($bankSoalKonteksId, $partSoalSaatIni->bank_soal_id);

            if ($this->layananUjian->idPartSoalDalamUjianAktif([$partSoalSaatIni->id]) !== []) {
                throw new \RuntimeException('Part soal tidak bisa dihapus karena sedang dipakai ujian aktif.');
            }

            if ($partSoalSaatIni->soal()->exists()) {
                throw new \RuntimeException('Part soal tidak bisa dihapus karena masih memiliki soal.');
            }

            $this->layananUjian->hapusPartSoalDariUjianNonaktif([$partSoalSaatIni->id]);
            $this->partSoalRepo->delete($partSoalSaatIni);
        });
    }

    public function dataDaftarFakultas(): array
    {
        return [
            'fakultas' => $this->fakultasRepo->semuaDenganJumlahProgramStudi(),
        ];
    }

    public function buatFakultas(array $data): void
    {
        $this->fakultasRepo->create($data);
    }

    public function perbaruiFakultas(Fakultas $fakultas, array $data): void
    {
        $this->fakultasRepo->update($fakultas, $data);
    }

    public function hapusFakultas(Fakultas $fakultas): void
    {
        $this->fakultasRepo->delete($fakultas);
    }

    public function dataDaftarProgramStudi(): array
    {
        return [
            'fakultas' => $this->fakultasRepo->semuaDenganJumlahProgramStudi(),
        ];
    }

    public function dataDaftarProgramStudiPerFakultas(Fakultas $fakultas): array
    {
        return [
            'fakultas' => $fakultas,
            'programStudi' => $this->programStudiRepo->semuaDenganFakultasId($fakultas->id),
        ];
    }

    public function dataTambahProgramStudiMassal(Fakultas $fakultas): array
    {
        return [
            'fakultas' => $fakultas,
        ];
    }

    public function buatProgramStudi(array $data): void
    {
        $this->programStudiRepo->create($data);
    }

    /**
     * @param  array<int, array{nama: string, kode: string, aktif?: bool}>  $data
     */
    public function buatBanyakProgramStudi(Fakultas $fakultas, array $data): void
    {
        $programStudi = array_map(
            static fn (array $row): array => [
                'fakultas_id' => $fakultas->id,
                'nama' => $row['nama'],
                'kode' => $row['kode'],
                'aktif' => $row['aktif'] ?? true,
            ],
            $data,
        );

        DB::transaction(function () use ($programStudi): void {
            $this->programStudiRepo->createMany($programStudi);
        });
    }

    public function perbaruiProgramStudi(ProgramStudi $programStudi, array $data): void
    {
        unset($data['fakultas_id']);

        $this->programStudiRepo->update($programStudi, $data);
    }

    public function hapusProgramStudi(ProgramStudi $programStudi): void
    {
        $this->programStudiRepo->delete($programStudi);
    }

    public function dataDaftarInterpretasiSkor(): array
    {
        return [
            'programTes' => $this->programTesRepo->semuaDenganJumlahBankSoal(),
        ];
    }

    public function dataDaftarInterpretasiSkorPerProgramTes(ProgramTes $programTes): array
    {
        return [
            'programTes' => $programTes,
            'interpretasiSkor' => $this->interpretasiSkorRepo->semuaDenganProgramTes($programTes->id),
        ];
    }

    public function buatInterpretasiSkor(array $data): void
    {
        $this->interpretasiSkorRepo->create($data);
    }

    /**
     * @param  array<int, array<string, mixed>>  $data
     */
    public function buatBanyakInterpretasiSkor(
        ProgramTes $programTes,
        array $data,
        ?string $skemaPenilaianId = null,
    ): void {
        $interpretasiSkor = array_map(
            static fn (array $row): array => [
                'skema_penilaian_id' => $skemaPenilaianId,
                'program_tes_id' => $programTes->id,
                'skor_minimum' => $row['skor_minimum'],
                'skor_maksimum' => $row['skor_maksimum'],
                'tingkat_cefr' => $row['tingkat_cefr'],
                'label_tingkat' => $row['label_tingkat'],
                'lulus' => $row['lulus'] ?? false,
                'deskripsi' => $row['deskripsi'] ?? null,
            ],
            $data,
        );

        DB::transaction(function () use ($interpretasiSkor): void {
            $this->interpretasiSkorRepo->createMany($interpretasiSkor);
        });
    }

    public function perbaruiInterpretasiSkor(InterpretasiSkor $interpretasi, array $data): void
    {
        $this->interpretasiSkorRepo->update($interpretasi, $data);
    }

    public function hapusInterpretasiSkor(InterpretasiSkor $interpretasi): void
    {
        $this->interpretasiSkorRepo->delete($interpretasi);
    }

    public function dataDaftarSertifikat(): array
    {
        return [
            'sertifikat' => $this->sertifikatRepo->semuaDenganRelasi(),
        ];
    }

    public function buatSertifikat(array $data): void
    {
        $this->sertifikatRepo->create($data);
    }

    public function hapusSertifikat(Sertifikat $sertifikat): void
    {
        $this->sertifikatRepo->delete($sertifikat);
    }

    public function fakultasAktif(): Collection
    {
        return $this->fakultasRepo->semuaAktifBerurutan();
    }

    public function programStudiAktif(): Collection
    {
        return $this->programStudiRepo->semuaAktifBerurutan();
    }

    public function partSoalAktif(): Collection
    {
        return $this->partSoalRepo->semuaAktifBerurutan();
    }

    public function sectionAktif(): Collection
    {
        return $this->sectionRepo->semuaAktifBerurutan();
    }

    public function programTesAktif(): Collection
    {
        return $this->programTesRepo->semuaAktifBerurutan();
    }

    private function bersihkanTeksOpsional(array $data): array
    {
        foreach (['deskripsi', 'petunjuk'] as $kolom) {
            if (! empty($data[$kolom])) {
                $data[$kolom] = Purifier::clean($data[$kolom]);
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function simpanKebijakanJikaDikirim(Section $section, array $data): void
    {
        if (! array_key_exists('ketentuan_materi', $data)
            && ! array_key_exists('aturan_media', $data)) {
            return;
        }

        $ketentuanMateri = $data['ketentuan_materi'] ?? null;
        $aturanMedia = $data['aturan_media'] ?? null;
        $adaNilaiPolicy = $ketentuanMateri !== null
            || (is_array($aturanMedia) && count(array_filter($aturanMedia, fn ($value): bool => $value !== null)) > 0);

        if (! $adaNilaiPolicy) {
            $this->kebijakanMateri->hapusUntukSection($section->id);

            return;
        }

        $jenisMedia = ['text', 'audio', 'image'];
        if (! is_string($ketentuanMateri)
            || ! is_array($aturanMedia)
            || array_diff($jenisMedia, array_keys($aturanMedia)) !== []
            || count(array_filter($aturanMedia, fn ($value): bool => $value === null)) > 0) {
            throw ValidationException::withMessages([
                'ketentuan_materi' => 'Konfigurasi materi dan seluruh aturan media harus diisi lengkap.',
            ]);
        }

        $this->kebijakanMateri->simpanUntukSection(
            $section->id,
            $ketentuanMateri,
            $aturanMedia,
        );
    }

    private function pastikanBankSoalKonteks(
        ?string $bankSoalKonteksId,
        ?string $bankSoalId = null,
        ?string $programTesId = null,
    ): void {
        if ($bankSoalKonteksId === null) {
            return;
        }

        $bankSoalKonteks = $this->bankSoalRepo->find($bankSoalKonteksId);
        if ($bankSoalKonteks === null
            || ($bankSoalId !== null && $bankSoalKonteksId !== $bankSoalId)
            || ($programTesId !== null && $bankSoalKonteks->program_tes_id !== $programTesId)) {
            throw ValidationException::withMessages([
                'bank_soal_konteks_id' => 'Konteks bank soal tidak sesuai dengan data yang diubah.',
            ]);
        }

        if ($this->bankSoalRepo->digunakanDalamUjian($bankSoalKonteksId)) {
            throw ValidationException::withMessages([
                'bank_soal_konteks_id' => 'Data bank soal tidak dapat diubah karena sudah digunakan di ujian.',
            ]);
        }
    }

    private function pastikanSectionMilikBank(string $bankSoalId, string $sectionId): void
    {
        $bankSoal = $this->bankSoalRepo->find($bankSoalId);
        $section = $this->sectionRepo->find($sectionId);

        if ($bankSoal === null || $section === null || $section->program_tes_id !== $bankSoal->program_tes_id) {
            throw ValidationException::withMessages([
                'section_id' => 'Section harus berasal dari program tes yang sama dengan bank soal.',
            ]);
        }
    }
}
