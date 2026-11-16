<?php

namespace App\Modules\Penilaian\DTOs;

class HasilPenilaianSesi
{
    /**
     * @param  array<int, HasilSkorSection>  $skorSection
     */
    public function __construct(
        public readonly string $skemaPenilaianId,
        public readonly array $skorSection,
        public readonly float $total,
        public readonly float $totalMaksimum,
        public readonly bool $lulus,
        public readonly ?string $tingkatCefr,
        public readonly ?string $labelTingkat,
        public readonly ?string $deskripsiTingkat,
    ) {}

    public function skorUntukNamaSection(string $nama): ?float
    {
        foreach ($this->skorSection as $hasil) {
            if (strcasecmp($hasil->namaSection, $nama) === 0) {
                return $hasil->skorSkala;
            }
        }

        return null;
    }
}
