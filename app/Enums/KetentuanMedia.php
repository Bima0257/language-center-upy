<?php

namespace App\Enums;

/**
 * Ketentuan keberadaan media pada materi soal.
 */
enum KetentuanMedia: string
{
    case REQUIRED = 'required';
    case OPTIONAL = 'optional';
    case FORBIDDEN = 'forbidden';

    /**
     * Label Bahasa Indonesia untuk tampilan.
     */
    public function label(): string
    {
        return match ($this) {
            self::REQUIRED => 'Wajib',
            self::OPTIONAL => 'Opsional',
            self::FORBIDDEN => 'Terlarang',
        };
    }
}
