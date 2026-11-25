<?php

namespace App\Modules\Penjadwalan\Services;

use App\Enums\ModeUjian;
use App\Models\PeriodeUjian;
use App\Models\ProgramTes;
use App\Models\SlotUjian;
use App\Models\Ujian;
use App\Modules\DataInduk\Repositories\Contracts\ProgramTesRepositoryInterface;
use App\Modules\Penjadwalan\Repositories\Contracts\PenjadwalanRepositoryInterface;
use App\Modules\Penjadwalan\Repositories\Contracts\SlotUjianRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\UjianRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class LayananPenjadwalan
{
    public function __construct(
        private PenjadwalanRepositoryInterface $penjadwalanRepo,
        private SlotUjianRepositoryInterface $slotRepo,
        private UjianRepositoryInterface $ujianRepo,
        private ProgramTesRepositoryInterface $programTesRepo,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dataDaftar(array $filters = []): array
    {
        $programTesId = $filters['f_program_tes_id'] ?? $filters['program_tes_id'] ?? null;
        $ujianId = $filters['f_ujian_id'] ?? $filters['ujian_id'] ?? null;
        $perPage = (int) ($filters['per_page'] ?? 15);
        $tableFilters = [
            'search' => $filters['q'] ?? $filters['search'] ?? null,
            'aktif' => $filters['f_aktif'] ?? $filters['aktif'] ?? null,
            'sort' => $filters['sort'] ?? 'tanggal_mulai',
            'dir' => $filters['dir'] ?? 'desc',
        ];

        $programTes = $this->programTesRepo->semuaAktifBerurutan();
        $ujianAktif = $this->ujianRepo->semuaAktifDenganProgramTes()
            ->filter(fn (Ujian $ujian): bool => $ujian->mode === ModeUjian::OFFICIAL)
            ->values();
        $jumlahUjianPerProgramTes = $ujianAktif->groupBy('program_tes_id')->map->count();
        $jumlahPeriodePerProgramTes = $this->penjadwalanRepo->jumlahPeriodePerProgramTes();
        $ringkasanProgramTes = $programTes
            ->filter(fn (ProgramTes $jenis): bool => $jumlahUjianPerProgramTes->has($jenis->id))
            ->map(fn (ProgramTes $jenis): array => [
                'id' => $jenis->id,
                'kode' => $jenis->kode,
                'nama' => $jenis->nama,
                'jumlahUjian' => (int) $jumlahUjianPerProgramTes->get($jenis->id, 0),
                'jumlahPeriode' => (int) $jumlahPeriodePerProgramTes->get($jenis->id, 0),
            ])
            ->values()
            ->all();

        $programTesTerpilih = $programTes->first(
            fn (ProgramTes $jenis): bool => $jenis->id === $programTesId
                && $jumlahUjianPerProgramTes->has($jenis->id),
        );
        $ujianUntukProgramTes = $programTesTerpilih !== null
            ? $ujianAktif->where('program_tes_id', $programTesTerpilih->id)->values()
            : new Collection;
        $jumlahPeriodePerUjian = $programTesTerpilih !== null
            ? $this->penjadwalanRepo->jumlahPeriodePerUjian($programTesTerpilih->id)
            : collect();
        $ringkasanUjian = $ujianUntukProgramTes
            ->map(fn (Ujian $ujian): array => [
                'id' => $ujian->id,
                'programTesId' => $ujian->program_tes_id,
                'judul' => $ujian->judul,
                'mode' => $ujian->mode->value,
                'jumlahPeriode' => (int) $jumlahPeriodePerUjian->get($ujian->id, 0),
            ])
            ->values()
            ->all();
        $ujianTerpilih = $ujianUntukProgramTes->firstWhere('id', $ujianId);

        return [
            'periodeUjian' => $ujianTerpilih !== null
                ? $this->penjadwalanRepo->paginateSemua($tableFilters, $perPage, $programTesTerpilih->id, $ujianTerpilih->id)
                : $this->penjadwalanRepo->paginatorKosong($perPage),
            'ringkasanProgramTes' => $ringkasanProgramTes,
            'ringkasanUjian' => $ringkasanUjian,
            'programTesTerpilih' => $programTesTerpilih,
            'programTesTerpilihId' => $programTesTerpilih?->id,
            'ujianTerpilih' => $ujianTerpilih,
            'ujianTerpilihId' => $ujianTerpilih?->id,
        ];
    }

    public function dataDetail(PeriodeUjian $periode): array
    {
        return [
            'periodeUjian' => $this->penjadwalanRepo->findOrFail($periode->id),
            'slotUjian' => $this->slotRepo->temukanDenganPeriodeUjian($periode->id),
        ];
    }

    public function buat(array $data): PeriodeUjian
    {
        $ujian = $this->ujianRepo->temukanDenganProgramTes($data['ujian_id']);

        if ($ujian->mode !== ModeUjian::OFFICIAL) {
            throw new \RuntimeException('Penjadwalan hanya tersedia untuk ujian resmi.');
        }

        $this->pastikanTidakAdaTanggalTumpangTindih($data);

        return $this->penjadwalanRepo->create($data);
    }

    public function perbarui(PeriodeUjian $periode, array $data): PeriodeUjian
    {
        $this->pastikanTidakAdaTanggalTumpangTindih(
            [...$data, 'ujian_id' => $periode->ujian_id],
            $periode->id,
        );

        return $this->penjadwalanRepo->update($periode, $data);
    }

    public function hapus(PeriodeUjian $periode): void
    {
        if ($this->penjadwalanRepo->memilikiSlot($periode)) {
            throw new \RuntimeException('Periode tidak bisa dihapus karena sudah memiliki sesi.');
        }

        $this->penjadwalanRepo->delete($periode);
    }

    public function buatSlot(PeriodeUjian $periode, array $data): SlotUjian
    {
        $tanggal = Carbon::parse($data['tanggal']);

        $this->pastikanTanggalDalamPeriode($periode, $tanggal);
        $this->pastikanTidakAdaWaktuTumpangTindih($periode->id, $tanggal, $data['waktu_mulai'], $data['waktu_selesai']);

        return $this->slotRepo->create([...$data, 'periode_ujian_id' => $periode->id]);
    }

    public function perbaruiSlot(SlotUjian $slotUjian, array $data): SlotUjian
    {
        $tanggal = Carbon::parse($data['tanggal']);

        $this->pastikanTanggalDalamPeriode($slotUjian->periodeUjian, $tanggal);
        $this->pastikanTidakAdaWaktuTumpangTindih($slotUjian->periode_ujian_id, $tanggal, $data['waktu_mulai'], $data['waktu_selesai'], $slotUjian->id);

        return $this->slotRepo->update($slotUjian, $data);
    }

    public function hapusSlot(SlotUjian $slotUjian): void
    {
        if ($this->slotRepo->memilikiSesi($slotUjian) || $this->slotRepo->memilikiPendaftaran($slotUjian)) {
            throw new \RuntimeException('Sesi tidak bisa dihapus karena sudah memiliki peserta atau pendaftaran.');
        }

        $this->slotRepo->delete($slotUjian);
    }

    private function pastikanTidakAdaTanggalTumpangTindih(array $data, ?string $kecualiId = null): void
    {
        if ($this->penjadwalanRepo->memilikiTanggalTumpangTindih(
            $data['ujian_id'],
            Carbon::parse($data['tanggal_mulai']),
            Carbon::parse($data['tanggal_selesai']),
            $kecualiId,
        )) {
            throw new \RuntimeException('Periode bertabrakan dengan periode lain pada ujian ini.');
        }
    }

    private function pastikanTanggalDalamPeriode(PeriodeUjian $periode, Carbon $tanggal): void
    {
        if ($tanggal->toDateString() < $periode->tanggal_mulai->toDateString()
            || $tanggal->toDateString() > $periode->tanggal_selesai->toDateString()) {
            throw new \RuntimeException('Tanggal sesi harus berada dalam rentang periode ujian.');
        }
    }

    private function pastikanTidakAdaWaktuTumpangTindih(string $periodeUjianId, Carbon $tanggal, string $waktuMulai, string $waktuSelesai, ?string $kecualiId = null): void
    {
        if ($this->slotRepo->memilikiWaktuTumpangTindih($periodeUjianId, $tanggal, $waktuMulai, $waktuSelesai, $kecualiId)) {
            throw new \RuntimeException('Sesi bertabrakan dengan sesi lain pada tanggal yang sama.');
        }
    }
}
