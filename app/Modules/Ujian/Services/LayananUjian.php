<?php

namespace App\Modules\Ujian\Services;

use App\Models\BagianUjian;
use App\Models\BankSoal;
use App\Models\Keterampilan;
use App\Models\Ujian;
use App\Modules\DataInduk\Repositories\Contracts\BagianSoalRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\JenisTesRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\KeterampilanRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\BagianUjianRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\BankSoalRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\SoalRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\UjianRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LayananUjian
{
    public function __construct(
        private UjianRepositoryInterface $ujianRepo,
        private BagianUjianRepositoryInterface $bagianRepo,
        private SoalRepositoryInterface $soalRepo,
        private BankSoalRepositoryInterface $bankSoalRepo,
        private JenisTesRepositoryInterface $jenisTesRepo,
        private KeterampilanRepositoryInterface $keterampilanRepo,
        private BagianSoalRepositoryInterface $bagianSoalRepo,
    ) {}

    public function dataDaftar(): Collection
    {
        return $this->ujianRepo->semuaUntukDaftar();
    }

    public function temukanDenganRelasi(string $id): ?Ujian
    {
        return $this->ujianRepo->temukanDenganRelasi($id);
    }

    public function buatDenganBagian(array $data): Ujian
    {
        $bank = $this->pastikanDataPembuatanUjian($data);

        return DB::transaction(function () use ($data, $bank) {
            $ujian = $this->ujianRepo->create($data);
            $bankId = $data['bank_soal_id'];

            $urutan = 1;
            foreach ($this->keterampilanRepo->semuaAktifBerurutan($ujian->jenis_tes_id) as $keterampilan) {
                $hasApproved = $this->soalRepo->disetujuiDenganIdKeterampilanUntukJenisTes(
                    $keterampilan->id,
                    $ujian->jenis_tes_id,
                    $bankId,
                )->isNotEmpty();

                if (! $hasApproved) {
                    continue;
                }

                $bagian = $this->bagianRepo->create([
                    'ujian_id' => $ujian->id,
                    'bank_soal_id' => $bankId,
                    'keterampilan_id' => $keterampilan->id,
                    'judul' => "{$keterampilan->nama} — {$bank->nama}",
                    'urutan' => $urutan++,
                ]);

                foreach ($this->bagianSoalRepo->semuaAktifDenganBank($bankId) as $bagianSoal) {
                    if ($bagianSoal->keterampilan_id !== $keterampilan->id) {
                        continue;
                    }

                    $this->bagianRepo->perbaruiAtauBuatPivotBagianSoal($bagian->id, $bagianSoal->id, $bagianSoal->urutan);
                }

                $this->lampirkanSoalDisetujui($ujian, $bagian, $keterampilan);
            }

            return $ujian;
        });
    }

    public function sinkronkanBagian(Ujian $ujian): int
    {
        $bankId = $ujian->bagianUjian->first()?->bank_soal_id;

        if ($bankId === null) {
            return 0;
        }

        $bank = $this->bankSoalRepo->find($bankId);
        $urutan = $ujian->bagianUjian->max('urutan') ?? 0;
        $dibuat = 0;

        foreach ($this->keterampilanRepo->semuaAktifBerurutan($ujian->jenis_tes_id) as $keterampilan) {
            if ($ujian->bagianUjian->contains('keterampilan_id', $keterampilan->id)) {
                continue;
            }

            $approved = $this->soalRepo->disetujuiDenganIdKeterampilanUntukJenisTes(
                $keterampilan->id,
                $ujian->jenis_tes_id,
                $bankId,
            );

            if ($approved->isEmpty()) {
                continue;
            }

            $bagian = $this->bagianRepo->create([
                'ujian_id' => $ujian->id,
                'bank_soal_id' => $bankId,
                'keterampilan_id' => $keterampilan->id,
                'judul' => "{$keterampilan->nama} — {$bank->nama}",
                'urutan' => ++$urutan,
            ]);

            foreach ($this->bagianSoalRepo->semuaAktifDenganBank($bankId) as $bagianSoal) {
                if ($bagianSoal->keterampilan_id === $keterampilan->id) {
                    $this->bagianRepo->perbaruiAtauBuatPivotBagianSoal($bagian->id, $bagianSoal->id, $bagianSoal->urutan);
                }
            }

            $this->lampirkanSoalDisetujui($ujian, $bagian, $keterampilan);
            $dibuat++;
        }

        return $dibuat;
    }

    public function perbarui(Ujian $ujian, array $data): Ujian
    {
        $targetJenisTesId = $data['jenis_tes_id'];
        $jenisTes = $this->jenisTesRepo->find($targetJenisTesId);

        if ($jenisTes === null) {
            throw ValidationException::withMessages([
                'jenis_tes_id' => 'Jenis tes tidak ditemukan.',
            ]);
        }

        if ($targetJenisTesId !== $ujian->jenis_tes_id) {
            if (! $jenisTes->aktif) {
                throw ValidationException::withMessages([
                    'jenis_tes_id' => 'Jenis tes tujuan harus aktif.',
                ]);
            }

            if ($ujian->bagianUjian()->exists() || $ujian->periodeUjian()->exists()) {
                throw ValidationException::withMessages([
                    'jenis_tes_id' => 'Jenis tes tidak dapat diubah setelah ujian memiliki bagian atau periode ujian.',
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
        $ujian = $this->ujianRepo->temukanDenganRelasi($ujian->id);
        $bagianIds = $ujian->bagianUjian->pluck('id');

        $soalDapatDilampirkan = [];
        foreach ($ujian->bagianUjian as $bagian) {
            $bankId = $bagian->bank_soal_id;

            $kecualiIds = $this->bagianRepo->idSoalDalamBagian($bagian->id);
            $soalDapatDilampirkan[$bagian->id] = $this->soalRepo->disetujuiDenganBankDanKeterampilanTanpa(
                $bankId,
                $bagian->keterampilan_id,
                $kecualiIds,
            );
        }

        return [
            'ujian' => $ujian,
            'opsiKeterampilan' => $this->keterampilanRepo->semuaAktifBerurutan($ujian->jenis_tes_id)->map(fn (Keterampilan $keterampilan) => ['value' => $keterampilan->id, 'label' => $keterampilan->nama, 'kode' => $keterampilan->kode])->toArray(),
            'bagianSoal' => $this->bagianSoalRepo->semuaAktifBerurutan(),
            'bankSoal' => $this->bankSoalRepo->semuaAktifBerurutan(),
            'soalPerBagian' => $this->bagianRepo->soalDikelompokkanDenganBagian($bagianIds),
            'bagianSoalPerBagian' => $this->bagianRepo->bagianSoalDikelompokkanDenganBagian($bagianIds),
            'soalDapatDilampirkan' => $soalDapatDilampirkan,
        ];
    }

    public function dataTambah(): array
    {
        $bankDenganKeterampilan = [];

        foreach ($this->keterampilanRepo->semuaAktifBerurutan() as $keterampilan) {
            $bankDenganKeterampilan[] = [
                'keterampilan_id' => $keterampilan->id,
                'label' => $keterampilan->nama,
                'bankSoal' => $this->soalRepo->bankDenganSoalDisetujuiDenganIdKeterampilanDanJenisTes($keterampilan->id, null),
            ];
        }

        return [
            'opsiKeterampilan' => $this->keterampilanRepo->semuaAktifBerurutan()->map(fn (Keterampilan $keterampilan) => ['value' => $keterampilan->id, 'label' => $keterampilan->nama, 'kode' => $keterampilan->kode])->toArray(),
            'bagianSoal' => $this->bagianSoalRepo->semuaAktifBerurutan(),
            'bankDenganKeterampilan' => $bankDenganKeterampilan,
        ];
    }

    public function bankSoalAktif(): Collection
    {
        return $this->bankSoalRepo->semuaAktifBerurutan();
    }

    public function dataUbah(Ujian $ujian): array
    {
        return [
            'ujian' => $this->ujianRepo->temukanDenganRelasi($ujian->id),
        ];
    }

    public function lampirkanSoalDisetujui(Ujian $ujian, BagianUjian $bagian, Keterampilan $keterampilan): int
    {
        $disetujui = $this->soalRepo->disetujuiDenganIdKeterampilanUntukJenisTes($keterampilan->id, $ujian->jenis_tes_id, $bagian->bank_soal_id);

        $nomor = 1;
        foreach ($disetujui as $soal) {
            $this->bagianRepo->buatAtauTemukanPivotSoal($bagian->id, $soal->id, $nomor++);
        }

        return $disetujui->count();
    }

    private function pastikanDataPembuatanUjian(array $data): BankSoal
    {
        $jenisTes = $this->jenisTesRepo->find($data['jenis_tes_id']);
        $bank = $this->bankSoalRepo->find($data['bank_soal_id']);

        if ($jenisTes === null || ! $jenisTes->aktif) {
            throw ValidationException::withMessages([
                'jenis_tes_id' => 'Jenis tes tidak aktif atau tidak ditemukan.',
            ]);
        }

        if ($bank === null || ! $bank->aktif) {
            throw ValidationException::withMessages([
                'bank_soal_id' => 'Bank soal tidak aktif atau tidak ditemukan.',
            ]);
        }

        if ($bank->jenis_tes_id !== $jenisTes->id) {
            throw ValidationException::withMessages([
                'bank_soal_id' => 'Bank soal harus berasal dari jenis tes yang dipilih.',
            ]);
        }

        return $bank;
    }
}
