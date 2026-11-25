<?php

namespace App\Modules\PelaksanaanUjian\Actions;

use App\Enums\StatusSesi;
use App\Models\Ujian;
use App\Modules\Keamanan\Services\LayananKeamanan;
use App\Modules\PelaksanaanUjian\DTOs\HasilSesiDimulai;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiPartUjianRepositoryInterface;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiUjianRepositoryInterface;
use App\Modules\PelaksanaanUjian\Services\LayananPelaksanaanUjian;
use App\Modules\PendaftaranUjian\Services\LayananPendaftaranUjian;
use App\Modules\Pengguna\Repositories\Contracts\PenggunaRepositoryInterface;
use App\Modules\Penilaian\Services\LayananAturanPenilaian;
use App\Modules\Penjadwalan\Repositories\Contracts\SlotUjianRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\UjianRepositoryInterface;
use App\Services\ImageCompressionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MulaiSesiUjian
{
    public function __construct(
        private SesiUjianRepositoryInterface $sesiRepo,
        private SesiPartUjianRepositoryInterface $sesiPartRepo,
        private SlotUjianRepositoryInterface $slotRepo,
        private UjianRepositoryInterface $ujianRepo,
        private LayananKeamanan $keamanan,
        private LayananPendaftaranUjian $layananPendaftaran,
        private PenggunaRepositoryInterface $penggunaRepo,
        private ImageCompressionService $imageCompression,
        private LayananAturanPenilaian $layananAturan,
        private LayananPelaksanaanUjian $layananPelaksanaan,
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
                        partPertamaId: $activeSession->part_ujian_aktif_id,
                    );
                }

                $pengguna = $this->penggunaRepo->findOrFail($penggunaId);
                $this->layananPendaftaran->pastikanBolehMulai(
                    $pengguna,
                    $slot,
                );

                $ujian = $slot->periodeUjian->ujian;
                $ujian->loadMissing('partUjian.section');
                $this->pastikanDurasiSectionValid($ujian);

                $snapshotPenilaian = $this->layananAturan->snapshotProgram($ujian->program_tes_id);
                $this->pastikanSoalSesuaiSkema($ujian, $snapshotPenilaian);

                $partPertama = $ujian->partUjian->sortBy('urutan')->first();

                if ($partPertama === null) {
                    throw new \RuntimeException('Ujian belum memiliki part soal.');
                }

                if ($swafoto !== null) {
                    $lokasiSwafoto = $this->imageCompression->storeCompressed($swafoto, 'selfies');
                }

                $mulaiPada = now();

                $sesi = $this->sesiRepo->create([
                    'slot_ujian_id' => $slot->id,
                    'ujian_id' => $ujian->id,
                    'pengguna_id' => $penggunaId,
                    'status' => StatusSesi::IN_PROGRESS,
                    'dimulai_pada' => $mulaiPada,
                    'part_ujian_aktif_id' => $partPertama->id,
                    'skema_penilaian_id' => $snapshotPenilaian['skema_penilaian_id'],
                    'snapshot_penilaian' => $snapshotPenilaian,
                    'skor_total_maksimum' => $snapshotPenilaian['skala_total_maksimum'],
                    'jenis_perangkat' => $jenisPerangkat,
                    'agen_pengguna_perangkat' => $agenPenggunaPerangkat,
                    'lokasi_swafoto' => $lokasiSwafoto,
                    'swafoto_diambil_pada' => $swafoto !== null ? now() : null,
                ]);

                $this->sesiPartRepo->mulai(
                    $sesi->id,
                    $partPertama->id,
                    (int) $partPertama->section->durasi_menit,
                    $mulaiPada,
                );

                $this->keamanan->catatPeristiwaSistem($sesi->id, 'exam_started');

                return new HasilSesiDimulai(
                    sesiId: $sesi->id,
                    partPertamaId: $partPertama->id,
                );
            });
        } catch (\Throwable $exception) {
            if ($lokasiSwafoto !== null) {
                Storage::disk('public')->delete($lokasiSwafoto);
            }

            throw $exception;
        }
    }

    public function mulaiTryout(string $penggunaId, string $ujianId): HasilSesiDimulai
    {
        return DB::transaction(function () use ($penggunaId, $ujianId): HasilSesiDimulai {
            $pengguna = $this->penggunaRepo->temukanUntukUpdate($penggunaId);
            $ujian = $this->ujianRepo->temukanTryout($ujianId);

            if ($ujian === null || ! $ujian->aktif || ! $ujian->programTes->aktif) {
                throw new \RuntimeException('Ujian tryout tidak tersedia atau sudah tidak aktif.');
            }

            if (! $pengguna->adalahPesertaUjian()) {
                throw new \RuntimeException('Hanya peserta ujian yang dapat mengikuti tryout.');
            }

            if (! $ujian->terbukaUntuk($pengguna)) {
                throw new \RuntimeException('Ujian ini tidak tersedia untuk program studi Anda.');
            }

            $activeSession = $this->sesiRepo->temukanSesiAktif($penggunaId);

            if ($activeSession !== null) {
                if ($activeSession->ujian_id === $ujian->id) {
                    return new HasilSesiDimulai(
                        sesiId: $activeSession->id,
                        partPertamaId: $activeSession->part_ujian_aktif_id,
                    );
                }

                throw new \RuntimeException('Selesaikan sesi ujian yang sedang berjalan terlebih dahulu.');
            }

            $snapshotPenilaian = $this->layananAturan->snapshotProgram($ujian->program_tes_id);
            $this->pastikanSoalSesuaiSkema($ujian, $snapshotPenilaian);
            $this->pastikanDurasiSectionValid($ujian);

            $partPertama = $ujian->partUjian->sortBy('urutan')->first();

            if ($partPertama === null) {
                throw new \RuntimeException('Ujian belum memiliki part soal.');
            }

            $mulaiPada = now();

            $sesi = $this->sesiRepo->create([
                'slot_ujian_id' => null,
                'ujian_id' => $ujian->id,
                'pengguna_id' => $pengguna->id,
                'status' => StatusSesi::IN_PROGRESS,
                'dimulai_pada' => $mulaiPada,
                'part_ujian_aktif_id' => $partPertama->id,
                'skema_penilaian_id' => $snapshotPenilaian['skema_penilaian_id'],
                'snapshot_penilaian' => $snapshotPenilaian,
                'skor_total_maksimum' => $snapshotPenilaian['skala_total_maksimum'],
            ]);

            $this->sesiPartRepo->mulai(
                $sesi->id,
                $partPertama->id,
                (int) $partPertama->section->durasi_menit,
                $mulaiPada,
            );

            $this->keamanan->catatPeristiwaSistem($sesi->id, 'exam_started');

            return new HasilSesiDimulai(
                sesiId: $sesi->id,
                partPertamaId: $partPertama->id,
            );
        });
    }

    private function pastikanDurasiSectionValid(Ujian $ujian): void
    {
        $ujian->loadMissing('partUjian.section');

        $parts = $ujian->partUjian;

        if ($parts->isEmpty()) {
            throw new \RuntimeException('Ujian belum memiliki part soal.');
        }

        $totalDurasiSection = 0;

        foreach ($parts as $part) {
            $totalDurasiSection += (int) $part->section->durasi_menit;
        }

        if ($totalDurasiSection > (int) $ujian->durasi_menit) {
            throw new \RuntimeException(
                "Total durasi section ({$totalDurasiSection} menit) melebihi durasi total ujian ({$ujian->durasi_menit} menit).",
            );
        }
    }

    /**
     * @param  array<string, mixed>  $snapshotPenilaian
     */
    private function pastikanSoalSesuaiSkema(Ujian $ujian, array $snapshotPenilaian): void
    {
        $soal = $this->layananPelaksanaan->soalUntukUjian($ujian);
        $soalPerSection = $soal->groupBy('section_id');
        $aturan = collect($snapshotPenilaian['aturan'] ?? []);

        $idSectionAturan = $aturan->pluck('section_id')->filter()->values();
        $idSectionSoal = $soalPerSection->keys()->filter()->values();
        $tanpaAturan = $idSectionSoal->diff($idSectionAturan);

        if ($tanpaAturan->isNotEmpty()) {
            throw new \RuntimeException(
                'Terdapat section pada ujian yang belum memiliki aturan penilaian: '.
                $tanpaAturan->implode(', ').'.',
            );
        }

        foreach ($aturan as $rule) {
            $sectionId = (string) ($rule['section_id'] ?? '');
            $namaSection = (string) ($rule['nama_section'] ?? $sectionId);
            $jumlahSoal = $soalPerSection->get($sectionId, collect())->count();
            $skorMentahMaksimum = (int) ($rule['skor_mentah_maksimum'] ?? 0);

            if ($jumlahSoal === 0) {
                throw new \RuntimeException(
                    "Ujian belum memiliki soal untuk section {$namaSection}.",
                );
            }

            if ($jumlahSoal > $skorMentahMaksimum) {
                throw new \RuntimeException(
                    "Jumlah soal {$namaSection} ({$jumlahSoal}) melebihi skor mentah (Raw) maksimum ({$skorMentahMaksimum}).",
                );
            }
        }
    }
}
