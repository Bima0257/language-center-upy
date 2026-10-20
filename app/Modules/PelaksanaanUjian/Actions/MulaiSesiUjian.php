<?php

namespace App\Modules\PelaksanaanUjian\Actions;

use App\Enums\StatusSesi;
use App\Modules\Keamanan\Services\LayananKeamanan;
use App\Modules\PelaksanaanUjian\DTOs\HasilSesiDimulai;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiUjianRepositoryInterface;
use App\Modules\Penjadwalan\Repositories\Contracts\SlotUjianRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

class MulaiSesiUjian
{
    public function __construct(
        private SesiUjianRepositoryInterface $sesiRepo,
        private SlotUjianRepositoryInterface $slotRepo,
        private LayananKeamanan $keamanan,
    ) {}

    public function execute(
        string $penggunaId,
        string $slotUjianId,
        ?string $jenisPerangkat = null,
        ?string $agenPenggunaPerangkat = null,
        ?UploadedFile $swafoto = null,
    ): HasilSesiDimulai {
        $slot = $this->slotRepo->findOrFail($slotUjianId);

        if (! $slot->aktif) {
            throw new \RuntimeException('Sesi ujian tidak aktif.');
        }

        if (! $slot->periodeUjian->sedangAktif()) {
            throw new \RuntimeException('Jadwal ujian ini sudah tidak berlaku.');
        }

        if (now()->toDateString() !== $slot->tanggal->toDateString()) {
            throw new \RuntimeException('Sesi ujian tidak tersedia pada hari ini. Jadwal: '.$slot->tanggal->locale('id')->isoFormat('dddd, D MMMM Y').'.');
        }

        $now = now()->format('H:i');
        $start = substr((string) $slot->waktu_mulai, 0, 5);
        $lateDeadline = Carbon::createFromFormat('H:i', $start)
            ->addMinutes($slot->toleransi_keterlambatan_menit)
            ->format('H:i');

        if ($now < $start) {
            throw new \RuntimeException('Sesi ujian belum dibuka. Mulai pukul '.$start.' WIB.');
        }

        if ($now > $lateDeadline) {
            throw new \RuntimeException('Waktu masuk ujian telah berakhir. Batas keterlambatan pukul '.$lateDeadline.' WIB.');
        }

        if ($slot->sesiUjian()->count() >= $slot->kapasitas_peserta) {
            throw new \RuntimeException('Kuota sesi ujian sudah penuh ('.$slot->kapasitas_peserta.' peserta).');
        }

        if ($this->sesiRepo->memilikiSesiUntukPeriode($penggunaId, $slot->periode_ujian_id)) {
            throw new \RuntimeException('Anda sudah mengikuti ujian pada periode ini. Satu percobaan per periode.');
        }

        $activeSession = $this->sesiRepo->temukanSesiAktif($penggunaId);
        if ($activeSession !== null) {
            return new HasilSesiDimulai(
                sesiId: $activeSession->id,
                bagianPertamaId: $activeSession->bagian_aktif_id,
            );
        }

        $bagianPertama = $slot->periodeUjian->ujian->bagianUjian->first();

        $selfiePath = null;
        if ($swafoto !== null) {
            $selfiePath = $swafoto->store('selfies', 'public');
        }

        $sesi = $this->sesiRepo->create([
            'slot_ujian_id' => $slot->id,
            'pengguna_id' => $penggunaId,
            'status' => StatusSesi::IN_PROGRESS,
            'dimulai_pada' => now(),
            'bagian_aktif_id' => $bagianPertama?->id,
            'jenis_perangkat' => $jenisPerangkat,
            'agen_pengguna_perangkat' => $agenPenggunaPerangkat,
            'lokasi_swafoto' => $selfiePath,
            'swafoto_diambil_pada' => $swafoto !== null ? now() : null,
        ]);

        $this->keamanan->catatPeristiwaSistem($sesi->id, 'exam_started');

        return new HasilSesiDimulai(
            sesiId: $sesi->id,
            bagianPertamaId: $bagianPertama?->id,
        );
    }
}
