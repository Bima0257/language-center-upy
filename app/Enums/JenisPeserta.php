<?php

namespace App\Enums;

enum JenisPeserta: string
{
    case MAHASISWA = 'mahasiswa';
    case DOSEN = 'dosen';
    case UMUM = 'umum';

    public function label(): string
    {
        return match ($this) {
            self::MAHASISWA => 'Mahasiswa',
            self::DOSEN => 'Dosen',
            self::UMUM => 'Umum',
        };
    }

    public function labelIdentitas(): string
    {
        return match ($this) {
            self::MAHASISWA => 'NIM',
            self::DOSEN => 'NIDN/NIP',
            self::UMUM => 'NIK',
        };
    }
}
