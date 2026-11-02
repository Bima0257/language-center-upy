<?php

namespace App\Modules\Ujian\Services;

use App\Enums\JenisMedia;
use App\Enums\KetentuanMedia;
use App\Models\KebijakanMateriSoal;
use App\Models\MateriSoal;
use App\Models\Soal;
use App\Modules\Ujian\DTOs\KonfigurasiKebijakanMateriSoal;
use App\Modules\Ujian\Repositories\Contracts\KebijakanMateriSoalRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\SoalRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LayananKebijakanMateriSoal
{
    public function __construct(
        private KebijakanMateriSoalRepositoryInterface $kebijakanRepo,
        private SoalRepositoryInterface $soalRepo,
    ) {}

    public function selesaikan(string $keterampilanId): ?KonfigurasiKebijakanMateriSoal
    {
        $kebijakan = $this->kebijakanRepo->temukanDenganKeterampilan($keterampilanId);

        if ($kebijakan === null) {
            return null;
        }

        $aturanMedia = [];

        foreach ($kebijakan->aturanMedia as $aturan) {
            $aturanMedia[$aturan->jenis_media->value] = $aturan->ketentuan->value;
        }

        return $this->buatKonfigurasi($kebijakan->ketentuan_materi->value, $aturanMedia);
    }

    public function selesaikanAtauGagal(string $keterampilanId): KonfigurasiKebijakanMateriSoal
    {
        $konfigurasi = $this->selesaikan($keterampilanId);

        if ($konfigurasi === null) {
            throw new \RuntimeException('Skill ini belum memiliki konfigurasi aturan materi soal. Atur kebijakan materi soal terlebih dahulu.');
        }

        return $konfigurasi;
    }

    /**
     * Material soal untuk skill: 'audio' atau 'text'. Null jika skill belum memiliki policy.
     */
    public function materiUntuk(string $keterampilanId): ?string
    {
        return $this->selesaikan($keterampilanId)?->materi();
    }

    /**
     * Simpan policy materi dan seluruh aturan media untuk satu keterampilan.
     *
     * @param  array<string, string>  $aturanMedia
     */
    public function simpanUntukKeterampilan(string $keterampilanId, string $ketentuanMateri, array $aturanMedia): void
    {
        $konfigurasi = $this->buatKonfigurasi($ketentuanMateri, $aturanMedia);

        DB::transaction(function () use ($keterampilanId, $konfigurasi): void {
            $kebijakan = $this->kebijakanRepo->temukanDenganKeterampilan($keterampilanId);
            $dataKebijakan = ['ketentuan_materi' => $konfigurasi->ketentuanMateri];

            $this->pastikanPerubahanTidakMerusakSoal($keterampilanId, $konfigurasi);

            if ($kebijakan === null) {
                $kebijakan = $this->kebijakanRepo->create([
                    'keterampilan_id' => $keterampilanId,
                    ...$dataKebijakan,
                ]);
            } else {
                $kebijakan = $this->kebijakanRepo->update($kebijakan, $dataKebijakan);
            }

            $this->kebijakanRepo->sinkronkanAturanMedia(
                $kebijakan,
                array_map(
                    fn (KetentuanMedia $ketentuan): string => $ketentuan->value,
                    $konfigurasi->aturanMedia,
                ),
            );
        });
    }

    public function hapusUntukKeterampilan(string $keterampilanId): void
    {
        $kebijakan = $this->kebijakanRepo->temukanDenganKeterampilan($keterampilanId);

        if ($kebijakan === null) {
            return;
        }

        if ($this->soalRepo->memilikiSoalDenganKeterampilan($keterampilanId)) {
            throw ValidationException::withMessages([
                'ketentuan_materi' => 'Policy materi tidak dapat dikosongkan karena keterampilan ini sudah dipakai oleh soal.',
            ]);
        }

        $this->kebijakanRepo->delete($kebijakan);
    }

    private function buatKonfigurasi(string $ketentuanMateri, array $aturanMedia): KonfigurasiKebijakanMateriSoal
    {
        $ketentuan = KetentuanMedia::tryFrom($ketentuanMateri);

        if ($ketentuan === null) {
            throw ValidationException::withMessages([
                'ketentuan_materi' => 'Penggunaan materi tidak valid.',
            ]);
        }

        $jenisMedia = array_map(
            fn (JenisMedia $jenis): string => $jenis->value,
            JenisMedia::cases(),
        );
        $kunciTidakDikenal = array_diff(array_keys($aturanMedia), $jenisMedia);
        $kunciBelumDiisi = array_diff($jenisMedia, array_keys($aturanMedia));

        if ($kunciTidakDikenal !== [] || $kunciBelumDiisi !== []) {
            throw ValidationException::withMessages([
                'aturan_media' => 'Aturan media harus memuat tepat Teks, Audio, dan Gambar.',
            ]);
        }

        $aturan = [];
        foreach ($jenisMedia as $jenis) {
            $nilai = $aturanMedia[$jenis];
            $aturan[$jenis] = is_string($nilai) ? KetentuanMedia::tryFrom($nilai) : null;

            if ($aturan[$jenis] === null) {
                throw ValidationException::withMessages([
                    'aturan_media.'.$jenis => 'Aturan media tidak valid.',
                ]);
            }
        }

        if ($ketentuan === KetentuanMedia::FORBIDDEN
            && count(array_filter($aturan, fn (KetentuanMedia $nilai): bool => $nilai !== KetentuanMedia::FORBIDDEN)) > 0) {
            throw ValidationException::withMessages([
                'ketentuan_materi' => 'Materi terlarang tidak boleh memiliki media yang wajib atau opsional.',
            ]);
        }

        if ($ketentuan === KetentuanMedia::OPTIONAL
            && count(array_filter($aturan, fn (KetentuanMedia $nilai): bool => $nilai === KetentuanMedia::REQUIRED)) > 0) {
            throw ValidationException::withMessages([
                'ketentuan_materi' => 'Materi opsional tidak boleh memiliki media wajib.',
            ]);
        }

        if ($ketentuan === KetentuanMedia::REQUIRED
            && count(array_filter($aturan, fn (KetentuanMedia $nilai): bool => $nilai !== KetentuanMedia::FORBIDDEN)) === 0) {
            throw ValidationException::withMessages([
                'ketentuan_materi' => 'Materi wajib harus mengizinkan minimal satu jenis media.',
            ]);
        }

        return new KonfigurasiKebijakanMateriSoal($ketentuan, $aturan);
    }

    private function pastikanPerubahanTidakMerusakSoal(string $keterampilanId, KonfigurasiKebijakanMateriSoal $konfigurasi): void
    {
        foreach ($this->soalRepo->soalUntukKeterampilan($keterampilanId) as $soal) {
            try {
                $this->pastikanMateriDiizinkan($konfigurasi, $soal->materiSoal, 'aturan_media');

                if ($konfigurasi->materi() === 'text') {
                    $this->pastikanIsiSoal($soal, 'ketentuan_materi');
                }
            } catch (ValidationException $exception) {
                $messages = $exception->errors()['aturan_media']
                    ?? $exception->errors()['ketentuan_materi']
                    ?? [];

                throw ValidationException::withMessages([
                    'ketentuan_materi' => $messages[0] ?? 'Policy baru akan membuat materi soal yang sudah ada menjadi tidak valid.',
                ]);
            }
        }
    }

    /**
     * Seluruh policy dalam bentuk payload frontend (per jenis tes + skill).
     *
     * @return array<int, array{jenis_tes_id: string, keterampilan_id: string, ketentuan_materi: string, materi: string, aturan_media: array<string, string>}>
     */
    public function semuaUntukFrontend(): array
    {
        return $this->kebijakanRepo->all()
            ->map(function (KebijakanMateriSoal $kebijakan): array {
                $aturanMedia = [];

                foreach ($kebijakan->aturanMedia as $aturan) {
                    $aturanMedia[$aturan->jenis_media->value] = $aturan->ketentuan->value;
                }

                $konfigurasi = $this->buatKonfigurasi($kebijakan->ketentuan_materi->value, $aturanMedia);

                return [
                    'jenis_tes_id' => $kebijakan->keterampilan->jenis_tes_id,
                    'keterampilan_id' => $kebijakan->keterampilan_id,
                    'ketentuan_materi' => $kebijakan->ketentuan_materi->value,
                    'materi' => $konfigurasi->materi(),
                    'aturan_media' => $aturanMedia,
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

        if (! $this->memilikiTeks($materi->isi_teks) && ! $adaMedia) {
            throw ValidationException::withMessages([$kunciKesalahan => 'Materi soal harus memiliki isi teks atau minimal satu media.']);
        }
    }

    public function pastikanAturanMedia(KonfigurasiKebijakanMateriSoal $konfigurasi, MateriSoal $materi, string $kunciKesalahan): void
    {
        foreach (JenisMedia::cases() as $jenis) {
            $ketentuan = $konfigurasi->ketentuanUntuk($jenis);

            $tersedia = match ($jenis) {
                JenisMedia::TEXT => $this->memilikiTeks($materi->isi_teks),
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
            $konfigurasi = $this->selesaikan($keterampilanId);

            if ($konfigurasi === null) {
                throw new \RuntimeException('Skill pada soal yang memakai materi ini belum memiliki konfigurasi aturan materi soal.');
            }

            try {
                $this->pastikanMateriDiizinkan($konfigurasi, $materi, 'materi_soal');
            } catch (ValidationException $exception) {
                $messages = $exception->errors()['materi_soal'] ?? [];

                throw new \RuntimeException($messages[0] ?? 'Materi soal melanggar aturan skill yang memakainya.');
            }
        }
    }

    private function pastikanIsiSoal(Soal $soal, string $kunciKesalahan): void
    {
        $pilihanKosong = collect(['pilihan_a', 'pilihan_b', 'pilihan_c', 'pilihan_d'])
            ->contains(fn (string $kolom): bool => empty(trim((string) $soal->getAttribute($kolom))));

        if (empty(trim((string) $soal->getAttribute('teks_soal'))) || $pilihanKosong) {
            throw ValidationException::withMessages([
                $kunciKesalahan => 'Perubahan policy akan membuat soal tipe teks tidak valid.',
            ]);
        }
    }

    private function memilikiTeks(?string $isi): bool
    {
        $teks = html_entity_decode(strip_tags($isi ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim($teks) !== '';
    }
}
