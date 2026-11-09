<?php

namespace App\Modules\PendaftaranUjian\Services;

use App\Models\JenisTes;
use App\Models\PeriodePendaftaranUjian;
use App\Modules\DataInduk\Repositories\Contracts\JenisTesRepositoryInterface;
use App\Modules\PendaftaranUjian\Repositories\Contracts\PendaftaranUjianRepositoryInterface;
use App\Modules\PendaftaranUjian\Repositories\Contracts\PeriodePendaftaranUjianRepositoryInterface;
use App\Modules\Penjadwalan\Repositories\Contracts\PenjadwalanRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\UjianRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class LayananPeriodePendaftaranUjian
{
    public function __construct(
        private PeriodePendaftaranUjianRepositoryInterface $periodePendaftaranRepo,
        private PendaftaranUjianRepositoryInterface $pendaftaranRepo,
        private PenjadwalanRepositoryInterface $penjadwalanRepo,
        private JenisTesRepositoryInterface $jenisTesRepo,
        private UjianRepositoryInterface $ujianRepo,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dataDaftar(array $filters): array
    {
        $jenisTes = $this->jenisTesRepo->semuaAktifBerurutan();
        $ujianAktif = $this->ujianRepo->semuaAktifDenganJenisTes();
        $jumlahUjianPerJenisTes = $ujianAktif->groupBy('jenis_tes_id')->map->count();
        $jumlahPeriodePerJenisTes = $this->periodePendaftaranRepo->jumlahPeriodePerJenisTes();
        $ringkasanJenisTes = $jenisTes
            ->filter(fn (JenisTes $jenis): bool => $jumlahUjianPerJenisTes->has($jenis->id))
            ->map(fn (JenisTes $jenis): array => [
                'id' => $jenis->id,
                'kode' => $jenis->kode,
                'nama' => $jenis->nama,
                'jumlahUjian' => (int) $jumlahUjianPerJenisTes->get($jenis->id, 0),
                'jumlahPeriodePendaftaran' => (int) $jumlahPeriodePerJenisTes->get($jenis->id, 0),
            ])
            ->values()
            ->all();

        $jenisTesTerpilih = $jenisTes->first(
            fn (JenisTes $jenis): bool => $jenis->id === ($filters['jenis_tes_id'] ?? null)
                && $jumlahUjianPerJenisTes->has($jenis->id),
        );
        $filters['jenis_tes_id'] = $jenisTesTerpilih?->id;

        $periodePendaftaran = $jenisTesTerpilih !== null
            ? $this->periodePendaftaranRepo->paginate($filters)
            : $this->periodePendaftaranRepo->paginatorKosong();
        $this->tambahkanStatus($periodePendaftaran);

        return [
            'periodePendaftaranUjian' => $periodePendaftaran,
            'periodeUjian' => $jenisTesTerpilih !== null
                ? $this->dataForm(null, $jenisTesTerpilih->id)['periodeUjian']
                : new Collection,
            'filters' => $filters,
            'ringkasanJenisTes' => $ringkasanJenisTes,
            'jenisTesTerpilih' => $jenisTesTerpilih,
            'jenisTesTerpilihId' => $jenisTesTerpilih?->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dataForm(?string $kecualiId = null, ?string $jenisTesId = null): array
    {
        return [
            'periodeUjian' => $this->penjadwalanRepo->semuaAktifUntukPendaftaran($kecualiId, $jenisTesId),
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
