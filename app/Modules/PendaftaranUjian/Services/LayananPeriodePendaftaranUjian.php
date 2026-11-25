<?php

namespace App\Modules\PendaftaranUjian\Services;

use App\Enums\ModeUjian;
use App\Models\PeriodePendaftaranUjian;
use App\Models\ProgramTes;
use App\Models\Ujian;
use App\Modules\DataInduk\Repositories\Contracts\ProgramTesRepositoryInterface;
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
        private ProgramTesRepositoryInterface $programTesRepo,
        private UjianRepositoryInterface $ujianRepo,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dataDaftar(array $filters): array
    {
        $filters['search'] = $filters['q'] ?? $filters['search'] ?? null;
        $filters['status'] = $filters['f_status'] ?? $filters['status'] ?? null;
        $filters['program_tes_id'] = $filters['f_program_tes_id'] ?? $filters['program_tes_id'] ?? null;
        $perPage = (int) ($filters['per_page'] ?? 15);

        $programTes = $this->programTesRepo->semuaAktifBerurutan();
        $ujianAktif = $this->ujianRepo->semuaAktifDenganProgramTes()
            ->filter(fn (Ujian $ujian): bool => $ujian->mode === ModeUjian::OFFICIAL)
            ->values();
        $jumlahUjianPerProgramTes = $ujianAktif->groupBy('program_tes_id')->map->count();
        $jumlahPeriodePerProgramTes = $this->periodePendaftaranRepo->jumlahPeriodePerProgramTes();
        $ringkasanProgramTes = $programTes
            ->filter(fn (ProgramTes $jenis): bool => $jumlahUjianPerProgramTes->has($jenis->id))
            ->map(fn (ProgramTes $jenis): array => [
                'id' => $jenis->id,
                'kode' => $jenis->kode,
                'nama' => $jenis->nama,
                'jumlahUjian' => (int) $jumlahUjianPerProgramTes->get($jenis->id, 0),
                'jumlahPeriodePendaftaran' => (int) $jumlahPeriodePerProgramTes->get($jenis->id, 0),
            ])
            ->values()
            ->all();

        $programTesTerpilih = $programTes->first(
            fn (ProgramTes $jenis): bool => $jenis->id === ($filters['program_tes_id'] ?? null)
                && $jumlahUjianPerProgramTes->has($jenis->id),
        );
        $filters['program_tes_id'] = $programTesTerpilih?->id;

        $periodePendaftaran = $programTesTerpilih !== null
            ? $this->periodePendaftaranRepo->paginate($filters, $perPage)
            : $this->periodePendaftaranRepo->paginatorKosong($perPage);
        $this->tambahkanStatus($periodePendaftaran);

        return [
            'periodePendaftaranUjian' => $periodePendaftaran,
            'periodeUjian' => $programTesTerpilih !== null
                ? $this->dataForm(null, $programTesTerpilih->id)['periodeUjian']
                : new Collection,
            'filters' => $filters,
            'ringkasanProgramTes' => $ringkasanProgramTes,
            'programTesTerpilih' => $programTesTerpilih,
            'programTesTerpilihId' => $programTesTerpilih?->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dataForm(?string $kecualiId = null, ?string $programTesId = null): array
    {
        return [
            'periodeUjian' => $this->penjadwalanRepo->semuaAktifUntukPendaftaran($kecualiId, $programTesId),
        ];
    }

    public function buat(array $data): PeriodePendaftaranUjian
    {
        $periodeUjian = $this->penjadwalanRepo->findOrFail($data['periode_ujian_id']);

        if ($periodeUjian->ujian->mode !== ModeUjian::OFFICIAL) {
            throw new \RuntimeException('Periode pendaftaran hanya tersedia untuk ujian resmi.');
        }

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
