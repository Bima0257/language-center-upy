<?php

namespace App\Modules\PelaksanaanUjian\DTOs;

class HasilPenyerahan
{
    public function __construct(
        public readonly string $sesiId,
        public readonly float $skorMembaca,
        public readonly float $skorMenyimak,
        public readonly float $skorTotal,
    ) {}
}
