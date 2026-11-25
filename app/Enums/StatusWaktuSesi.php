<?php

namespace App\Enums;

/**
 * Status hasil sinkronisasi waktu sesi ujian.
 */
enum StatusWaktuSesi: string
{
    case LANJUT = 'lanjut';
    case PINDAH_SECTION = 'pindah_section';
    case SEMUA_SECTION_SELESAI = 'semua_section_selesai';
    case TOTAL_HABIS = 'total_habis';
    case TIDAK_AKTIF = 'tidak_aktif';
}
