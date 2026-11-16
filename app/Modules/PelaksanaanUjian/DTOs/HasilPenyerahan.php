<?php

namespace App\Modules\PelaksanaanUjian\DTOs;

class HasilPenyerahan
{
    public function __construct(
        public readonly string $sesiId,
        public readonly float $skorMembaca,
        public readonly float $skorMenyimak,
        public readonly float $skorTotal,
        public readonly float $skorTotalMaksimum,
        public readonly bool $lulus,
        public readonly ?string $tingkatCefr = null,
        public readonly ?string $labelTingkat = null,
    ) {}
}
