<?php

namespace App\Enums;

/**
 * Alasan sebuah part/section ujian ditutup.
 */
enum AlasanSelesaiPart: string
{
    case MANUAL = 'manual';
    case WAKTU_HABIS = 'waktu_habis';
    case DISERAHKAN = 'diserahkan';
    case DIBATALKAN = 'dibatalkan';
}
