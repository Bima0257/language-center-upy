<?php

namespace App\Modules\PelaksanaanUjian\Actions;

use App\Enums\AlasanSelesaiPart;
use App\Enums\StatusSesi;
use App\Enums\StatusWaktuSesi;
use App\Models\PartUjian;
use App\Models\SesiUjian;
use App\Models\Ujian;
use App\Modules\PelaksanaanUjian\DTOs\HasilSinkronisasiWaktu;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiPartUjianRepositoryInterface;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiUjianRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\PartUjianRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SinkronkanWaktuSesi
{
    public function __construct(
        private SesiUjianRepositoryInterface $sesiRepo,
        private SesiPartUjianRepositoryInterface $sesiPartRepo,
        private PartUjianRepositoryInterface $partRepo,
    ) {}

    public function execute(SesiUjian $sesi): HasilSinkronisasiWaktu
    {
        return DB::transaction(function () use ($sesi): HasilSinkronisasiWaktu {
            $sesi = $this->sesiRepo->temukanUntukUpdate($sesi->id);
            $ujian = $sesi->ujian ?? $sesi->slotUjian?->periodeUjian?->ujian;

            if ($sesi->status !== StatusSesi::IN_PROGRESS || $ujian === null || $sesi->dimulai_pada === null) {
                return $this->hasilTidakAktif($sesi, $ujian);
            }

            $parts = $this->partRepo->semuaUntukUjian($ujian);

            if ($parts->isEmpty()) {
                throw new \RuntimeException('Ujian belum memiliki part soal.');
            }

            $now = now();
            $totalDeadline = $sesi->dimulai_pada->copy()->addMinutes($ujian->durasi_menit);

            if ($now->getTimestamp() >= $totalDeadline->getTimestamp()) {
                $this->sesiPartRepo->selesaikanSemuaTerbuka(
                    $sesi->id,
                    AlasanSelesaiPart::WAKTU_HABIS,
                    $totalDeadline,
                );

                return $this->buatHasil(
                    sesi: $sesi,
                    ujian: $ujian,
                    status: StatusWaktuSesi::TOTAL_HABIS,
                    part: $sesi->partUjianAktif,
                    parts: $parts,
                    batasSection: null,
                    totalDeadline: $totalDeadline,
                );
            }

            $part = $sesi->partUjianAktif;

            if ($part === null) {
                $part = $parts->first();
                $sesi = $this->sesiRepo->update($sesi->id, ['part_ujian_aktif_id' => $part->id]);
            }

            $timing = $this->sesiPartRepo->temukanAktifUntukUpdate($sesi->id);

            if ($timing === null || $timing->part_ujian_id !== $part->id) {
                if ($timing !== null) {
                    $this->sesiPartRepo->selesaikan($timing, AlasanSelesaiPart::DIBATALKAN, $now);
                }

                $timing = $this->sesiPartRepo->mulai(
                    $sesi->id,
                    $part->id,
                    (int) $part->section->durasi_menit,
                    $now,
                );
            }

            $pindahSection = false;
            $batasSection = $timing->dimulai_pada->copy()->addMinutes($timing->durasi_menit);

            while ($now->getTimestamp() >= $batasSection->getTimestamp()) {
                $this->sesiPartRepo->selesaikan($timing, AlasanSelesaiPart::WAKTU_HABIS, $batasSection);

                $partBerikutnya = $this->partRepo->partBerikutnya($ujian->id, $part->urutan);

                if ($partBerikutnya === null) {
                    return $this->buatHasil(
                        sesi: $sesi,
                        ujian: $ujian,
                        status: StatusWaktuSesi::SEMUA_SECTION_SELESAI,
                        part: $part,
                        parts: $parts,
                        batasSection: null,
                        totalDeadline: $totalDeadline,
                    );
                }

                $sesi = $this->sesiRepo->update($sesi->id, ['part_ujian_aktif_id' => $partBerikutnya->id]);
                $part = $partBerikutnya;
                $pindahSection = true;

                $timing = $this->sesiPartRepo->mulai(
                    $sesi->id,
                    $part->id,
                    (int) $part->section->durasi_menit,
                    $batasSection,
                );

                $batasSection = $timing->dimulai_pada->copy()->addMinutes($timing->durasi_menit);
            }

            return $this->buatHasil(
                sesi: $sesi,
                ujian: $ujian,
                status: $pindahSection ? StatusWaktuSesi::PINDAH_SECTION : StatusWaktuSesi::LANJUT,
                part: $part,
                parts: $parts,
                batasSection: $batasSection,
                totalDeadline: $totalDeadline,
            );
        });
    }

    /**
     * @param  Collection<int, PartUjian>  $parts
     */
    private function buatHasil(
        SesiUjian $sesi,
        Ujian $ujian,
        StatusWaktuSesi $status,
        ?PartUjian $part,
        $parts,
        ?Carbon $batasSection,
        Carbon $totalDeadline,
    ): HasilSinkronisasiWaktu {
        $sekarang = now()->getTimestamp();
        $urutanSection = $part === null
            ? null
            : (($index = $parts->search(fn (PartUjian $item): bool => $item->id === $part->id)) === false
                ? null
                : $index + 1);

        return new HasilSinkronisasiWaktu(
            sesiId: $sesi->id,
            status: $status,
            partUjianAktifId: $part?->id,
            namaSection: $part?->section?->nama,
            urutanSection: $urutanSection,
            jumlahSection: $parts->count(),
            sectionTerakhir: $urutanSection !== null && $urutanSection === $parts->count(),
            sisaDetikSection: $batasSection === null
                ? null
                : max(0, $batasSection->getTimestamp() - $sekarang),
            sisaDetikTotal: max(0, $totalDeadline->getTimestamp() - $sekarang),
            durasiDetikTotal: (int) $ujian->durasi_menit * 60,
            durasiDetikSection: $part?->section !== null
                ? (int) $part->section->durasi_menit * 60
                : null,
        );
    }

    private function hasilTidakAktif(SesiUjian $sesi, ?Ujian $ujian): HasilSinkronisasiWaktu
    {
        $part = $sesi->partUjianAktif;

        return new HasilSinkronisasiWaktu(
            sesiId: $sesi->id,
            status: StatusWaktuSesi::TIDAK_AKTIF,
            partUjianAktifId: $part?->id,
            namaSection: $part?->section?->nama,
            urutanSection: null,
            jumlahSection: 0,
            sectionTerakhir: false,
            sisaDetikSection: null,
            sisaDetikTotal: 0,
            durasiDetikTotal: $ujian !== null ? (int) $ujian->durasi_menit * 60 : 0,
            durasiDetikSection: null,
            sesiAktif: false,
        );
    }
}
