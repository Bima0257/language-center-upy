<?php

namespace App\Enums;

/**
 * Lingkup peserta yang dapat mengikuti ujian.
 */
enum LingkupUjian: string
{
    case UMUM = 'umum';
    case PRODI = 'prodi';

    public function label(): string
    {
        return match ($this) {
            self::UMUM => 'Semua Peserta',
            self::PRODI => 'Prodi Tertentu',
        };
    }
}
