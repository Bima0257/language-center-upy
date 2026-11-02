<?php

namespace App\Modules\PelaksanaanUjian\Actions;

use App\Enums\ModeUjian;
use App\Enums\StatusSesi;
use App\Modules\Keamanan\Services\LayananKeamanan;
use App\Modules\PelaksanaanUjian\DTOs\HasilSesiDimulai;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiUjianRepositoryInterface;
use App\Modules\PendaftaranUjian\Services\LayananPendaftaranUjian;
use App\Modules\Pengguna\Repositories\Contracts\PenggunaRepositoryInterface;
use App\Modules\Penjadwalan\Repositories\Contracts\SlotUjianRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MulaiSesiUjian
{
    public function __construct(
        private SesiUjianRepositoryInterface $sesiRepo,
        private SlotUjianRepositoryInterface $slotRepo,
        private LayananKeamanan $keamanan,
        private LayananPendaftaranUjian $layananPendaftaran,
        private PenggunaRepositoryInterface $penggunaRepo,
    ) {}

    public function execute(
        string $penggunaId,
        string $slotUjianId,
        ?string $jenisPerangkat = null,
        ?string $agenPenggunaPerangkat = null,
        ?UploadedFile $swafoto = null,
    ): HasilSesiDimulai {
        $lokasiSwafoto = null;

        try {
            return DB::transaction(function () use (
                $penggunaId,
                $slotUjianId,
                $jenisPerangkat,
                $agenPenggunaPerangkat,
                $swafoto,
                &$lokasiSwafoto,
            ): HasilSesiDimulai {
                $slot = $this->slotRepo->temukanUntukUpdate($slotUjianId);

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

                $pengguna = $this->penggunaRepo->findOrFail($penggunaId);
                if ($slot->periodeUjian->ujian->mode === ModeUjian::OFFICIAL) {
                    $this->layananPendaftaran->pastikanBolehMulai(
                        $pengguna,
                        $slot,
                    );
                } else {
                    $this->layananPendaftaran->daftarkanTryout(
                        $pengguna,
                        $slot,
                    );
                }

                $bagianPertama = $slot->periodeUjian->ujian->bagianUjian->first();

                if ($swafoto !== null) {
                    $lokasiSwafoto = $swafoto->store('selfies', 'public');
                }

                $sesi = $this->sesiRepo->create([
                    'slot_ujian_id' => $slot->id,
                    'pengguna_id' => $penggunaId,
                    'status' => StatusSesi::IN_PROGRESS,
                    'dimulai_pada' => now(),
                    'bagian_aktif_id' => $bagianPertama?->id,
                    'jenis_perangkat' => $jenisPerangkat,
                    'agen_pengguna_perangkat' => $agenPenggunaPerangkat,
                    'lokasi_swafoto' => $lokasiSwafoto,
                    'swafoto_diambil_pada' => $swafoto !== null ? now() : null,
                ]);

                $this->keamanan->catatPeristiwaSistem($sesi->id, 'exam_started');

                return new HasilSesiDimulai(
                    sesiId: $sesi->id,
                    bagianPertamaId: $bagianPertama?->id,
                );
            });
        } catch (\Throwable $exception) {
            if ($lokasiSwafoto !== null) {
                Storage::disk('public')->delete($lokasiSwafoto);
            }

            throw $exception;
        }
    }
}
