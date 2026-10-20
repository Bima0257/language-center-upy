<?php

namespace App\Modules\PelaksanaanUjian\DTOs;

class HasilSesiDimulai
{
    public function __construct(
        public readonly string $sesiId,
        public readonly ?string $bagianPertamaId,
    ) {}
}
