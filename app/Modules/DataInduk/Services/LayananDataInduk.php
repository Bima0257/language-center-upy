<?php

namespace App\Modules\DataInduk\Services;

use App\Models\BagianSoal;
use App\Models\Fakultas;
use App\Models\InterpretasiSkor;
use App\Models\JenisTes;
use App\Models\Keterampilan;
use App\Models\ProgramStudi;
use App\Models\Sertifikat;
use App\Modules\DataInduk\Repositories\Contracts\BagianSoalRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\FakultasRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\InterpretasiSkorRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\JenisTesRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\KeterampilanRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\ProgramStudiRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\SertifikatRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\UrutanKodeRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\BankSoalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mews\Purifier\Facades\Purifier;

class LayananDataInduk
{
    public function __construct(
        private JenisTesRepositoryInterface $jenisTesRepo,
        private KeterampilanRepositoryInterface $keterampilanRepo,
        private BagianSoalRepositoryInterface $bagianSoalRepo,
        private FakultasRepositoryInterface $fakultasRepo,
        private ProgramStudiRepositoryInterface $programStudiRepo,
        private InterpretasiSkorRepositoryInterface $interpretasiSkorRepo,
        private SertifikatRepositoryInterface $sertifikatRepo,
        private UrutanKodeRepositoryInterface $urutanKodeRepo,
        private BankSoalRepositoryInterface $bankSoalRepo,
    ) {}

    // ===== Skills =====
    public function dataDaftarKeterampilan(): array
    {
        return [
            'jenisTes' => $this->jenisTesRepo->semuaDenganJumlahBankSoal(),
        ];
    }

    public function dataDaftarKeterampilanPerJenisTes(JenisTes $jenisTes): array
    {
        return [
            'jenisTes' => $jenisTes,
            'keterampilan' => $this->keterampilanRepo->semuaBerurutan($jenisTes->id),
        ];
    }

    public function buatKeterampilan(array $data): Keterampilan
    {
        return DB::transaction(function () use ($data): Keterampilan {
            $data = $this->bersihkanTeksOpsional($data);
            $jenisTes = $this->jenisTesRepo->find($data['jenis_tes_id']);

            if ($jenisTes === null || ! $jenisTes->aktif) {
                throw new \RuntimeException('Jenis tes tidak aktif atau tidak ditemukan.');
            }

            $ruangKode = 'keterampilan:'.$jenisTes->kode;
            $nomorKode = $this->urutanKodeRepo->nilaiBerikutnya($ruangKode);
            $data['kode'] = $jenisTes->kode.str_pad((string) $nomorKode, 3, '0', STR_PAD_LEFT);
            $data['urutan'] = $data['urutan'] ?? $this->keterampilanRepo->urutanBerikutnya($jenisTes->id);

            return $this->keterampilanRepo->create($data);
        });
    }

    public function perbaruiKeterampilan(Keterampilan $keterampilan, array $data): void
    {
        $data = $this->bersihkanTeksOpsional($data);

        $this->keterampilanRepo->update($keterampilan, $data);
    }

    public function hapusKeterampilan(Keterampilan $keterampilan): void
    {
        $memilikiAturanPenilaian = $keterampilan->jenisTes()
            ->whereHas('aturanPenilaian', fn ($query) => $query->where('kode_keterampilan', $keterampilan->kode))
            ->exists();

        if ($keterampilan->bagianSoal()->exists()
            || $keterampilan->soal()->exists()
            || $keterampilan->bagianUjian()->exists()
            || $keterampilan->kebijakanMateriSoal()->exists()
            || $memilikiAturanPenilaian) {
            throw new \RuntimeException('Skill tidak bisa dihapus karena masih digunakan di part soal, soal, atau section ujian.');
        }

        $this->keterampilanRepo->delete($keterampilan);
    }

    /**
     * Terima daftar id skill dalam urutan baru; order dihitung ulang berdasarkan posisi.
     *
     * @param  array<string>  $ids
     */
    public function susunUlangKeterampilan(array $ids, string $jenisTesId): void
    {
        $keterampilan = $this->keterampilanRepo->temukanBanyak($ids, $jenisTesId);

        foreach ($ids as $index => $id) {
            $dataKeterampilan = $keterampilan->firstWhere('id', $id);
            if (! $dataKeterampilan) {
                continue;
            }

            $urutanBaru = $index + 1;
            if ($dataKeterampilan->urutan !== $urutanBaru) {
                $this->keterampilanRepo->update($dataKeterampilan, ['urutan' => $urutanBaru]);
            }
        }
    }

    // ===== Exam Types =====
    public function dataDaftarJenisTes(): array
    {
        return [
            'jenisTes' => $this->jenisTesRepo->semuaDenganJumlahBankSoal(),
        ];
    }

    public function buatJenisTes(array $data): JenisTes
    {
        return DB::transaction(function () use ($data): JenisTes {
            $data = $this->bersihkanTeksOpsional($data);
            $data['kode'] = str_pad((string) $this->urutanKodeRepo->nilaiBerikutnya('jenis_tes'), 3, '0', STR_PAD_LEFT);
            $jenisTes = $this->jenisTesRepo->create($data);

            $this->urutanKodeRepo->pastikanRuang('keterampilan:'.$jenisTes->kode);

            return $jenisTes;
        });
    }

    public function perbaruiJenisTes(JenisTes $jenisTes, array $data): void
    {
        $this->jenisTesRepo->update($jenisTes, $this->bersihkanTeksOpsional($data));
    }

    public function hapusJenisTes(JenisTes $jenisTes): void
    {
        if ($jenisTes->bankSoal()->exists()
            || $jenisTes->keterampilan()->exists()
            || $jenisTes->ujian()->exists()
            || $jenisTes->aturanPenilaian()->exists()
            || $jenisTes->interpretasiSkor()->exists()) {
            throw new \RuntimeException('Jenis tes tidak bisa dihapus karena masih digunakan konfigurasi ujian.');
        }

        $this->jenisTesRepo->delete($jenisTes);
    }

    public function dataDaftarBagianSoal(?string $bankSoalId = null, ?string $jenisTesId = null): array
    {
        $bagianSoal = $this->bagianSoalRepo->semuaDenganJumlahSoal();
        $bankSoalTerpilih = $bankSoalId !== null ? $this->bankSoalRepo->find($bankSoalId) : null;
        $jenisTesId = $bankSoalTerpilih !== null ? $bankSoalTerpilih->jenis_tes_id : $jenisTesId;
        $bankSoal = $this->bankSoalRepo->semuaAktifDenganJenisTesBerurutan($jenisTesId);

        if ($bankSoalId !== null) {
            $bagianSoal = $bagianSoal->where('bank_soal_id', $bankSoalId)->values();
        } elseif ($jenisTesId !== null) {
            $bagianSoal = $bagianSoal->whereIn('bank_soal_id', $bankSoal->pluck('id'))->values();
        }

        return [
            'bagianSoal' => $bagianSoal,
            'bankSoal' => $bankSoal,
            'jenisTes' => $this->jenisTesRepo->semuaAktifBerurutan(),
            'bankSoalTerpilihId' => $bankSoalId,
            'jenisTesTerpilihId' => $jenisTesId,
            'opsiKeterampilan' => $this->keterampilanRepo->semuaAktifBerurutan($jenisTesId)->map(fn (Keterampilan $keterampilan) => ['value' => $keterampilan->id, 'label' => $keterampilan->nama, 'kode' => $keterampilan->kode])->toArray(),
        ];
    }

    public function buatBagianSoal(array $data): void
    {
        $data = $this->bersihkanTeksOpsional($data);
        $this->pastikanKeterampilanMilikBank($data['bank_soal_id'], $data['keterampilan_id']);
        $data['urutan'] = $this->bagianSoalRepo->urutanBerikutnya($data['bank_soal_id'], $data['keterampilan_id']);

        $this->bagianSoalRepo->create($data);
    }

    public function perbaruiBagianSoal(BagianSoal $bagianSoal, array $data): void
    {
        $data = $this->bersihkanTeksOpsional($data);

        // Pindah bank/skill → urutan otomatis di akhir grup baru (hindari tabrakan urutan)
        $bankSoalId = $data['bank_soal_id'] ?? $bagianSoal->bank_soal_id;
        $keterampilanId = $data['keterampilan_id'] ?? $bagianSoal->keterampilan_id;
        $this->pastikanKeterampilanMilikBank($bankSoalId, $keterampilanId);

        if ($bankSoalId !== $bagianSoal->bank_soal_id || $keterampilanId !== $bagianSoal->keterampilan_id) {
            $data['urutan'] = $this->bagianSoalRepo->urutanBerikutnya($bankSoalId, $keterampilanId);
        }

        $this->bagianSoalRepo->update($bagianSoal, $data);
    }

    /**
     * Terima daftar id part dalam urutan baru; order dihitung ulang per (bank, skill).
     *
     * @param  array<string>  $ids
     */
    public function susunUlangBagianSoal(array $ids): void
    {
        $bagianSoal = $this->bagianSoalRepo->temukanBanyak($ids);
        $peringkat = [];

        foreach ($ids as $index => $id) {
            $dataBagianSoal = $bagianSoal->firstWhere('id', $id);
            if (! $dataBagianSoal) {
                continue;
            }

            $kunciKelompok = $dataBagianSoal->bank_soal_id.'|'.$dataBagianSoal->keterampilan_id;
            $peringkat[$kunciKelompok] = ($peringkat[$kunciKelompok] ?? 0) + 1;

            if ($dataBagianSoal->urutan !== $peringkat[$kunciKelompok]) {
                $this->bagianSoalRepo->update($dataBagianSoal, ['urutan' => $peringkat[$kunciKelompok]]);
            }
        }
    }

    public function hapusBagianSoal(BagianSoal $bagianSoal): void
    {
        $this->bagianSoalRepo->delete($bagianSoal);
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
            'jenisTes' => $this->jenisTesRepo->semuaDenganJumlahBankSoal(),
        ];
    }

    public function dataDaftarInterpretasiSkorPerJenisTes(JenisTes $jenisTes): array
    {
        return [
            'jenisTes' => $jenisTes,
            'interpretasiSkor' => $this->interpretasiSkorRepo->semuaDenganJenisTes($jenisTes->id),
        ];
    }

    public function buatInterpretasiSkor(array $data): void
    {
        $this->interpretasiSkorRepo->create($data);
    }

    /**
     * @param  array<int, array<string, mixed>>  $data
     */
    public function buatBanyakInterpretasiSkor(JenisTes $jenisTes, array $data): void
    {
        $interpretasiSkor = array_map(
            static fn (array $row): array => [
                'jenis_tes_id' => $jenisTes->id,
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

    public function bagianSoalAktif(): Collection
    {
        return $this->bagianSoalRepo->semuaAktifBerurutan();
    }

    public function keterampilanAktif(): Collection
    {
        return $this->keterampilanRepo->semuaAktifBerurutan();
    }

    public function jenisTesAktif(): Collection
    {
        return $this->jenisTesRepo->semuaAktifBerurutan();
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

    private function pastikanKeterampilanMilikBank(string $bankSoalId, string $keterampilanId): void
    {
        $bankSoal = $this->bankSoalRepo->find($bankSoalId);
        $keterampilan = $this->keterampilanRepo->find($keterampilanId);

        if ($bankSoal === null || $keterampilan === null || $keterampilan->jenis_tes_id !== $bankSoal->jenis_tes_id) {
            throw ValidationException::withMessages([
                'keterampilan_id' => 'Keterampilan harus berasal dari jenis tes yang sama dengan bank soal.',
            ]);
        }
    }
}
