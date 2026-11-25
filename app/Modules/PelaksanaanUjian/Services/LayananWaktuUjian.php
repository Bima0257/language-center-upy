<?php

namespace App\Modules\PelaksanaanUjian\Services;

use App\Enums\AlasanSelesaiPart;
use App\Enums\StatusSesi;
use App\Enums\StatusWaktuSesi;
use App\Models\SesiUjian;
use App\Modules\PelaksanaanUjian\Actions\AkhiriSesiKarenaWaktu;
use App\Modules\PelaksanaanUjian\Actions\SinkronkanWaktuSesi;
use App\Modules\PelaksanaanUjian\DTOs\HasilSinkronisasiWaktu;
use App\Modules\PelaksanaanUjian\DTOs\HasilWaktuUjian;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\JawabanRepositoryInterface;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiPartUjianRepositoryInterface;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiUjianRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\PartUjianRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LayananWaktuUjian
{
    public function __construct(
        private SinkronkanWaktuSesi $sinkronkanWaktu,
        private AkhiriSesiKarenaWaktu $akhiriSesi,
        private SesiUjianRepositoryInterface $sesiRepo,
        private SesiPartUjianRepositoryInterface $sesiPartRepo,
        private PartUjianRepositoryInterface $partRepo,
        private JawabanRepositoryInterface $jawabanRepo,
        private LayananPelaksanaanUjian $layananPelaksanaan,
    ) {}

    public function sinkronkan(SesiUjian $sesi): HasilSinkronisasiWaktu
    {
        return $this->sinkronkanWaktu->execute($sesi);
    }

    public function proses(SesiUjian $sesi): HasilWaktuUjian
    {
        return $this->akhiriBilaSelesai($this->sinkronkanWaktu->execute($sesi));
    }

    public function selesaikanSection(SesiUjian $sesi, string $partUjianId): HasilWaktuUjian
    {
        $waktu = $this->sinkronkanWaktu->execute($sesi);

        if (! $waktu->sesiAktif || $waktu->selesai()) {
            return $this->akhiriBilaSelesai($waktu);
        }

        if ($waktu->partUjianAktifId !== $partUjianId) {
            return new HasilWaktuUjian($waktu);
        }

        $sesi = $this->sesiRepo->findOrFail($sesi->id);
        $partAktif = $sesi->partUjianAktif;

        if ($partAktif === null || $partAktif->id !== $partUjianId) {
            return new HasilWaktuUjian($waktu);
        }

        $soalIds = $this->layananPelaksanaan->idSoalUntukSectionAktif($sesi);
        $jumlahSoal = count($soalIds);
        $jumlahTerjawab = $this->jawabanRepo->hitungTerjawab($sesi->id, $soalIds);

        if ($jumlahTerjawab < $jumlahSoal) {
            throw ValidationException::withMessages([
                'section' => 'Semua soal pada section ini harus terjawab sebelum menyelesaikan section.',
            ]);
        }

        [$sesiSetelah, $sectionTerakhir] = DB::transaction(function () use ($sesi, $partAktif): array {
            $sesiTerkini = $this->sesiRepo->temukanUntukUpdate($sesi->id);

            if ($sesiTerkini->status !== StatusSesi::IN_PROGRESS) {
                return [$sesiTerkini, false];
            }

            $partSaatIni = $sesiTerkini->partUjianAktif;

            if ($partSaatIni === null || $partSaatIni->id !== $partAktif->id) {
                return [$sesiTerkini, false];
            }

            $sekarang = now();
            $timing = $this->sesiPartRepo->temukanAktifUntukUpdate($sesiTerkini->id);

            if ($timing === null) {
                $timing = $this->sesiPartRepo->mulai(
                    $sesiTerkini->id,
                    $partSaatIni->id,
                    (int) $partSaatIni->section->durasi_menit,
                    $sekarang,
                );
            }

            $this->sesiPartRepo->selesaikan($timing, AlasanSelesaiPart::MANUAL, $sekarang);

            $partBerikutnya = $this->partRepo->partBerikutnya($sesiTerkini->ujian_id, $partSaatIni->urutan);

            if ($partBerikutnya === null) {
                return [$sesiTerkini, true];
            }

            $sesiTerkini = $this->sesiRepo->update($sesiTerkini->id, [
                'part_ujian_aktif_id' => $partBerikutnya->id,
            ]);

            $this->sesiPartRepo->mulai(
                $sesiTerkini->id,
                $partBerikutnya->id,
                (int) $partBerikutnya->section->durasi_menit,
                $sekarang,
            );

            return [$sesiTerkini, false];
        });

        if ($sectionTerakhir) {
            $penyerahan = $this->akhiriSesi->execute($sesiSetelah->id, AlasanSelesaiPart::MANUAL);
            $sesiTerkini = $this->sesiRepo->findOrFail($sesiSetelah->id);
            $sudahDiserahkan = $penyerahan !== null || $sesiTerkini->status === StatusSesi::SUBMITTED;

            return new HasilWaktuUjian(
                waktu: $waktu,
                diserahkan: $sudahDiserahkan,
                pesan: $sudahDiserahkan
                    ? 'Ujian telah selesai dan dinilai.'
                    : 'Sesi ujian dihentikan karena penilaian gagal diproses.',
            );
        }

        return new HasilWaktuUjian(
            $this->sinkronkanWaktu->execute($sesiSetelah)->denganStatus(StatusWaktuSesi::PINDAH_SECTION),
        );
    }

    private function akhiriBilaSelesai(HasilSinkronisasiWaktu $waktu): HasilWaktuUjian
    {
        if (! $waktu->sesiAktif) {
            return new HasilWaktuUjian($waktu);
        }

        if (! $waktu->selesai()) {
            return new HasilWaktuUjian($waktu);
        }

        $penyerahan = $this->akhiriSesi->execute($waktu->sesiId, AlasanSelesaiPart::WAKTU_HABIS);
        $sesiTerkini = $this->sesiRepo->findOrFail($waktu->sesiId);
        $sudahDiserahkan = $penyerahan !== null || $sesiTerkini->status === StatusSesi::SUBMITTED;

        return new HasilWaktuUjian(
            waktu: $waktu,
            diserahkan: $sudahDiserahkan,
            pesan: $sudahDiserahkan
                ? 'Ujian telah selesai dan dinilai.'
                : 'Sesi ujian dihentikan karena penilaian gagal diproses.',
        );
    }
}
