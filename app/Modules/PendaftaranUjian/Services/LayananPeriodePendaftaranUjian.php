<?php

namespace App\Modules\PendaftaranUjian\Services;

use App\Models\PeriodePendaftaranUjian;
use App\Modules\PendaftaranUjian\Repositories\Contracts\PendaftaranUjianRepositoryInterface;
use App\Modules\PendaftaranUjian\Repositories\Contracts\PeriodePendaftaranUjianRepositoryInterface;
use App\Modules\Penjadwalan\Repositories\Contracts\PenjadwalanRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LayananPeriodePendaftaranUjian
{
    public function __construct(
        private PeriodePendaftaranUjianRepositoryInterface $periodePendaftaranRepo,
        private PendaftaranUjianRepositoryInterface $pendaftaranRepo,
        private PenjadwalanRepositoryInterface $penjadwalanRepo,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dataDaftar(array $filters): array
    {
        $periodePendaftaran = $this->periodePendaftaranRepo->paginate($filters);
        $this->tambahkanStatus($periodePendaftaran);

        return [
            'periodePendaftaranUjian' => $periodePendaftaran,
            'periodeUjian' => $this->dataForm()['periodeUjian'],
            'filters' => $filters,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dataForm(?string $kecualiId = null): array
    {
        return [
            'periodeUjian' => $this->penjadwalanRepo->semuaAktifUntukPendaftaran($kecualiId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dataDetail(PeriodePendaftaranUjian $periodePendaftaran): array
    {
        $periodePendaftaran = $this->periodePendaftaranRepo->temukan($periodePendaftaran->id);
        $periodePendaftaran->setAttribute('status_pendaftaran', $periodePendaftaran->status());

        return [
            'periodePendaftaranUjian' => $periodePendaftaran,
            'slotUjian' => $periodePendaftaran->slotUjian,
        ];
    }

    public function buat(array $data): PeriodePendaftaranUjian
    {
        if ($this->periodePendaftaranRepo->temukanUntukPeriodeUjian($data['periode_ujian_id']) !== null) {
            throw new \RuntimeException('Periode ujian tersebut sudah memiliki periode pendaftaran.');
        }

        return $this->periodePendaftaranRepo->buat($data);
    }

    public function perbarui(PeriodePendaftaranUjian $periodePendaftaran, array $data): PeriodePendaftaranUjian
    {
        return $this->periodePendaftaranRepo->perbarui($periodePendaftaran, $data);
    }

    public function hapus(PeriodePendaftaranUjian $periodePendaftaran): void
    {
        if ($this->pendaftaranRepo->memilikiUntukPeriodeUjian($periodePendaftaran->periode_ujian_id)) {
            throw new \RuntimeException('Periode pendaftaran tidak dapat dihapus karena sudah memiliki pendaftaran peserta.');
        }

        if ($this->penjadwalanRepo->memilikiSesiUntukPeriodeUjian($periodePendaftaran->periode_ujian_id)) {
            throw new \RuntimeException('Periode pendaftaran tidak dapat dihapus karena sudah memiliki sesi ujian.');
        }

        $this->periodePendaftaranRepo->hapus($periodePendaftaran);
    }

    private function tambahkanStatus(LengthAwarePaginator $periodePendaftaran): void
    {
        foreach ($periodePendaftaran->items() as $periode) {
            if (! $periode instanceof PeriodePendaftaranUjian) {
                continue;
            }

            $periode->setAttribute('status_pendaftaran', $periode->status());
        }
    }
}
