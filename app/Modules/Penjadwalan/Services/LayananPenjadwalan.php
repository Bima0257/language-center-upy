<?php

namespace App\Modules\Penjadwalan\Services;

use App\Models\PeriodeUjian;
use App\Models\SlotUjian;
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
    ) {}

    public function dataDaftar(int $perPage = 15): array
    {
        return [
            'periodeUjian' => $this->penjadwalanRepo->paginateSemua($perPage),
            'ujian' => $this->ujianRepo->semuaAktifBerurutan(),
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
        $this->pastikanTidakAdaTanggalTumpangTindih($data);

        return $this->penjadwalanRepo->create($data);
    }

    public function perbarui(PeriodeUjian $periode, array $data): PeriodeUjian
    {
        $this->pastikanTidakAdaTanggalTumpangTindih($data, $periode->id);

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
        if ($this->slotRepo->memilikiSesi($slotUjian)) {
            throw new \RuntimeException('Sesi tidak bisa dihapus karena sudah diikuti peserta.');
        }

        $this->slotRepo->delete($slotUjian);
    }

    public function slotTersedia(): Collection
    {
        return $this->slotRepo->slotTersedia();
    }

    public function hitungSlotTersediaSekarang(): int
    {
        return $this->slotRepo->slotTersedia()->count();
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
