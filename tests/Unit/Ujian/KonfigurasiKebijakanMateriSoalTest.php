<?php

namespace Tests\Unit\Ujian;

use App\Enums\JenisMedia;
use App\Enums\KetentuanMedia;
use App\Modules\Ujian\DTOs\KonfigurasiKebijakanMateriSoal;
use PHPUnit\Framework\TestCase;

class KonfigurasiKebijakanMateriSoalTest extends TestCase
{
    private function konfigurasi(
        KetentuanMedia $teks,
        KetentuanMedia $audio,
        KetentuanMedia $gambar,
    ): KonfigurasiKebijakanMateriSoal {
        return new KonfigurasiKebijakanMateriSoal(KetentuanMedia::REQUIRED, [
            JenisMedia::TEXT->value => $teks,
            JenisMedia::AUDIO->value => $audio,
            JenisMedia::IMAGE->value => $gambar,
        ]);
    }

    public function test_materi_audio_ketika_audio_wajib(): void
    {
        $konfigurasi = $this->konfigurasi(
            KetentuanMedia::FORBIDDEN,
            KetentuanMedia::REQUIRED,
            KetentuanMedia::OPTIONAL,
        );

        $this->assertSame('audio', $konfigurasi->materi());
    }

    public function test_materi_teks_ketika_audio_terlarang(): void
    {
        $konfigurasi = $this->konfigurasi(
            KetentuanMedia::OPTIONAL,
            KetentuanMedia::FORBIDDEN,
            KetentuanMedia::OPTIONAL,
        );

        $this->assertSame('text', $konfigurasi->materi());
    }

    public function test_materi_audio_ketika_audio_opsional_dan_teks_terlarang(): void
    {
        $konfigurasi = $this->konfigurasi(
            KetentuanMedia::FORBIDDEN,
            KetentuanMedia::OPTIONAL,
            KetentuanMedia::OPTIONAL,
        );

        $this->assertSame('audio', $konfigurasi->materi());
    }

    public function test_materi_teks_ketika_audio_dan_teks_opsional(): void
    {
        $konfigurasi = $this->konfigurasi(
            KetentuanMedia::OPTIONAL,
            KetentuanMedia::OPTIONAL,
            KetentuanMedia::OPTIONAL,
        );

        $this->assertSame('text', $konfigurasi->materi());
    }

    public function test_materi_teks_ketika_audio_terlarang_dan_gambar_wajib(): void
    {
        $konfigurasi = $this->konfigurasi(
            KetentuanMedia::FORBIDDEN,
            KetentuanMedia::FORBIDDEN,
            KetentuanMedia::REQUIRED,
        );

        $this->assertSame('text', $konfigurasi->materi());
    }
}
