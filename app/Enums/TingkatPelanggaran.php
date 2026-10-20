<?php

namespace App\Enums;

/**
 * Tingkat dampak sebuah pelanggaran.
 */
enum TingkatPelanggaran: string
{
    case WARNING = 'warning';
    case MINOR = 'minor';
    case MAJOR = 'major';
    case CRITICAL = 'critical';

    /**
     * Jumlah pelanggaran yang ditambahkan ke sesi.
     */
    public function penambahanPelanggaran(): int
    {
        return match ($this) {
            self::WARNING => 0,
            self::MINOR => 1,
            self::MAJOR => 2,
            self::CRITICAL => 3,
        };
    }
}
