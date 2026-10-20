<?php

namespace App\Modules\Ujian\Services;

use App\Enums\JenisMedia;
use App\Enums\KetentuanMedia;
use App\Models\KebijakanMateriSoal;
use App\Models\MateriSoal;
use App\Modules\Ujian\DTOs\KonfigurasiKebijakanMateriSoal;
use App\Modules\Ujian\Repositories\Contracts\KebijakanMateriSoalRepositoryInterface;
use Illuminate\Validation\ValidationException;

class LayananKebijakanMateriSoal
{
    public function __construct(
        private KebijakanMateriSoalRepositoryInterface $kebijakanRepo,
    ) {}

    public function selesaikan(string $bankSoalId, string $keterampilanId): ?KonfigurasiKebijakanMateriSoal
    {
        $kebijakan = $this->kebijakanRepo->temukanDenganBankDanKeterampilan($bankSoalId, $keterampilanId);

        if ($kebijakan === null) {
            return null;
        }

        $aturanMedia = [];

        foreach ($kebijakan->aturanMedia as $aturan) {
            $aturanMedia[$aturan->jenis_media->value] = $aturan->ketentuan;
        }

        return new KonfigurasiKebijakanMateriSoal($kebijakan->ketentuan_materi, $aturanMedia);
    }

    public function selesaikanAtauGagal(string $bankSoalId, string $keterampilanId): KonfigurasiKebijakanMateriSoal
    {
        $konfigurasi = $this->selesaikan($bankSoalId, $keterampilanId);

        if ($konfigurasi === null) {
            throw new \RuntimeException('Skill ini belum memiliki konfigurasi aturan materi soal. Atur kebijakan materi soal terlebih dahulu.');
        }

        return $konfigurasi;
    }

    /**
     * Material soal untuk skill: 'audio' atau 'text'. Null jika skill belum memiliki policy.
     */
    public function materiUntuk(string $bankSoalId, string $keterampilanId): ?string
    {
        return $this->selesaikan($bankSoalId, $keterampilanId)?->materi();
    }

    /**
     * Seluruh policy dalam bentuk payload frontend (per kombinasi bank + skill).
     *
     * @return array<int, array{bank_soal_id: string, keterampilan_id: string, ketentuan_materi: string, materi: string}>
     */
    public function semuaUntukFrontend(): array
    {
        return $this->kebijakanRepo->all()
            ->map(function (KebijakanMateriSoal $kebijakan): array {
                $aturanMedia = [];

                foreach ($kebijakan->aturanMedia as $aturan) {
                    $aturanMedia[$aturan->jenis_media->value] = $aturan->ketentuan;
                }

                $konfigurasi = new KonfigurasiKebijakanMateriSoal($kebijakan->ketentuan_materi, $aturanMedia);

                return [
                    'bank_soal_id' => $kebijakan->bank_soal_id,
                    'keterampilan_id' => $kebijakan->keterampilan_id,
                    'ketentuan_materi' => $kebijakan->ketentuan_materi->value,
                    'materi' => $konfigurasi->materi(),
                ];
            })
            ->values()
            ->all();
    }

    public function pastikanMateriDiizinkan(KonfigurasiKebijakanMateriSoal $konfigurasi, ?MateriSoal $materi, string $kunciKesalahan): void
    {
        if ($konfigurasi->ketentuanMateri === KetentuanMedia::FORBIDDEN) {
            if ($materi !== null) {
                throw ValidationException::withMessages([$kunciKesalahan => 'Skill ini tidak menggunakan materi soal.']);
            }

            return;
        }

        if ($konfigurasi->ketentuanMateri === KetentuanMedia::REQUIRED && $materi === null) {
            throw ValidationException::withMessages([$kunciKesalahan => 'Skill ini wajib menggunakan materi soal.']);
        }

        if ($materi === null) {
            return;
        }

        $this->pastikanIsiMateri($materi, $kunciKesalahan);
        $this->pastikanAturanMedia($konfigurasi, $materi, $kunciKesalahan);
    }

    public function pastikanIsiMateri(MateriSoal $materi, string $kunciKesalahan): void
    {
        $adaMedia = $materi->memiliki_audio || $materi->memiliki_gambar;

        if (empty($materi->isi_teks) && ! $adaMedia) {
            throw ValidationException::withMessages([$kunciKesalahan => 'Materi soal harus memiliki isi teks atau minimal satu media.']);
        }
    }

    public function pastikanAturanMedia(KonfigurasiKebijakanMateriSoal $konfigurasi, MateriSoal $materi, string $kunciKesalahan): void
    {
        foreach (JenisMedia::cases() as $jenis) {
            $ketentuan = $konfigurasi->ketentuanUntuk($jenis);

            $tersedia = match ($jenis) {
                JenisMedia::TEXT => ! empty($materi->isi_teks),
                JenisMedia::AUDIO => $materi->memiliki_audio,
                JenisMedia::IMAGE => $materi->memiliki_gambar,
            };

            if ($ketentuan === KetentuanMedia::REQUIRED && ! $tersedia) {
                throw ValidationException::withMessages([$kunciKesalahan => 'Materi soal wajib memiliki '.$jenis->label().' sesuai aturan skill.']);
            }

            if ($ketentuan === KetentuanMedia::FORBIDDEN && $tersedia) {
                throw ValidationException::withMessages([$kunciKesalahan => 'Materi soal tidak boleh memiliki '.$jenis->label().' sesuai aturan skill.']);
            }
        }
    }

    /**
     * Pastikan passage tetap memenuhi policy setiap skill dari soal yang memakainya.
     * Dipakai saat mengubah isi/media passage agar soal existing tidak rusak.
     */
    public function pastikanMateriMemenuhiKeterampilan(MateriSoal $materi): void
    {
        $keterampilanIds = $materi->soal()->distinct()->pluck('keterampilan_id');

        foreach ($keterampilanIds as $keterampilanId) {
            $konfigurasi = $this->selesaikan($materi->bank_soal_id, $keterampilanId);

            if ($konfigurasi === null) {
                throw new \RuntimeException('Skill pada soal yang memakai materi ini belum memiliki konfigurasi aturan materi soal.');
            }

            try {
                $this->pastikanAturanMedia($konfigurasi, $materi, 'materi_soal');
            } catch (ValidationException $exception) {
                $messages = $exception->errors()['materi_soal'] ?? [];

                throw new \RuntimeException($messages[0] ?? 'Materi soal melanggar aturan skill yang memakainya.');
            }
        }
    }
}
