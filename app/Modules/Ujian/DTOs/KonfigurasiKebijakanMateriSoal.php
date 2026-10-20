<?php

namespace App\Modules\Ujian\DTOs;

use App\Enums\JenisMedia;
use App\Enums\KetentuanMedia;

final class KonfigurasiKebijakanMateriSoal
{
    /**
     * @param  array<string, KetentuanMedia>  $aturanMedia
     */
    public function __construct(
        public readonly KetentuanMedia $ketentuanMateri,
        public readonly array $aturanMedia,
    ) {}

    public function ketentuanUntuk(JenisMedia $jenis): KetentuanMedia
    {
        return $this->aturanMedia[$jenis->value] ?? KetentuanMedia::FORBIDDEN;
    }

    public function materi(): string
    {
        return $this->ketentuanUntuk(JenisMedia::AUDIO) === KetentuanMedia::REQUIRED ? 'audio' : 'text';
    }
}
