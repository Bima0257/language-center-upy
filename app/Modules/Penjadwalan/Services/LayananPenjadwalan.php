<?php

namespace App\Modules\Penjadwalan\Services;

use App\Models\JenisTes;
use App\Models\PeriodeUjian;
use App\Models\SlotUjian;
use App\Models\Ujian;
use App\Modules\DataInduk\Repositories\Contracts\JenisTesRepositoryInterface;
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
        private JenisTesRepositoryInterface $jenisTesRepo,
    ) {}

    public function dataDaftar(
        ?string $jenisTesId = null,
        ?string $ujianId = null,
        int $perPage = 15,
    ): array {
        $jenisTes = $this->jenisTesRepo->semuaAktifBerurutan();
        $ujianAktif = $this->ujianRepo->semuaAktifDenganJenisTes();
        $jumlahUjianPerJenisTes = $ujianAktif->groupBy('jenis_tes_id')->map->count();
        $jumlahPeriodePerJenisTes = $this->penjadwalanRepo->jumlahPeriodePerJenisTes();
        $ringkasanJenisTes = $jenisTes
            ->filter(fn (JenisTes $jenis): bool => $jumlahUjianPerJenisTes->has($jenis->id))
            ->map(fn (JenisTes $jenis): array => [
                'id' => $jenis->id,
                'kode' => $jenis->kode,
                'nama' => $jenis->nama,
                'jumlahUjian' => (int) $jumlahUjianPerJenisTes->get($jenis->id, 0),
                'jumlahPeriode' => (int) $jumlahPeriodePerJenisTes->get($jenis->id, 0),
            ])
            ->values()
            ->all();

        $jenisTesTerpilih = $jenisTes->first(
            fn (JenisTes $jenis): bool => $jenis->id === $jenisTesId
                && $jumlahUjianPerJenisTes->has($jenis->id),
        );
        $ujianUntukJenisTes = $jenisTesTerpilih !== null
            ? $ujianAktif->where('jenis_tes_id', $jenisTesTerpilih->id)->values()
            : new Collection;
        $jumlahPeriodePerUjian = $jenisTesTerpilih !== null
            ? $this->penjadwalanRepo->jumlahPeriodePerUjian($jenisTesTerpilih->id)
            : collect();
        $ringkasanUjian = $ujianUntukJenisTes
            ->map(fn (Ujian $ujian): array => [
                'id' => $ujian->id,
                'jenisTesId' => $ujian->jenis_tes_id,
                'judul' => $ujian->judul,
                'mode' => $ujian->mode->value,
                'jumlahPeriode' => (int) $jumlahPeriodePerUjian->get($ujian->id, 0),
            ])
            ->values()
            ->all();
        $ujianTerpilih = $ujianUntukJenisTes->firstWhere('id', $ujianId);

        return [
            'periodeUjian' => $ujianTerpilih !== null
                ? $this->penjadwalanRepo->paginateSemua($perPage, $jenisTesTerpilih->id, $ujianTerpilih->id)
                : $this->penjadwalanRepo->paginatorKosong($perPage),
            'ringkasanJenisTes' => $ringkasanJenisTes,
            'ringkasanUjian' => $ringkasanUjian,
            'jenisTesTerpilih' => $jenisTesTerpilih,
            'jenisTesTerpilihId' => $jenisTesTerpilih?->id,
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
