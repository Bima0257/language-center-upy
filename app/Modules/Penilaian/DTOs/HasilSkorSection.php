<?php

namespace App\Modules\Penilaian\DTOs;

class HasilSkorSection
{
    /**
     * @param  array<int, array<string, mixed>>  $rincian
     */
    public function __construct(
        public readonly string $sectionId,
        public readonly string $kodeSection,
        public readonly string $namaSection,
        public readonly int $jumlahBenar,
        public readonly int $jumlahSoal,
        public readonly float $skorSkala,
        public readonly array $rincian,
    ) {}
}
