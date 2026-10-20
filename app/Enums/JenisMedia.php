<?php

namespace App\Enums;

/**
 * Jenis media yang dapat dipasang pada materi soal.
 */
enum JenisMedia: string
{
    case TEXT = 'text';
    case AUDIO = 'audio';
    case IMAGE = 'image';

    /**
     * Label Bahasa Indonesia untuk tampilan.
     */
    public function label(): string
    {
        return match ($this) {
            self::TEXT => 'Teks',
            self::AUDIO => 'Audio',
            self::IMAGE => 'Gambar',
        };
    }
}
