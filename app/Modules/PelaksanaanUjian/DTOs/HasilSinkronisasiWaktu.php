<?php

namespace App\Modules\PelaksanaanUjian\DTOs;

use App\Enums\StatusWaktuSesi;

class HasilSinkronisasiWaktu
{
    public function __construct(
        public readonly string $sesiId,
        public readonly StatusWaktuSesi $status,
        public readonly ?string $partUjianAktifId,
        public readonly ?string $namaSection,
        public readonly ?int $urutanSection,
        public readonly int $jumlahSection,
        public readonly bool $sectionTerakhir,
        public readonly ?int $sisaDetikSection,
        public readonly int $sisaDetikTotal,
        public readonly int $durasiDetikTotal,
        public readonly ?int $durasiDetikSection,
        public readonly bool $sesiAktif = true,
    ) {}

    public function selesai(): bool
    {
        return in_array($this->status, [StatusWaktuSesi::SEMUA_SECTION_SELESAI, StatusWaktuSesi::TOTAL_HABIS], true);
    }

    public function denganStatus(StatusWaktuSesi $status): self
    {
        return new self(
            sesiId: $this->sesiId,
            status: $status,
            partUjianAktifId: $this->partUjianAktifId,
            namaSection: $this->namaSection,
            urutanSection: $this->urutanSection,
            jumlahSection: $this->jumlahSection,
            sectionTerakhir: $this->sectionTerakhir,
            sisaDetikSection: $this->sisaDetikSection,
            sisaDetikTotal: $this->sisaDetikTotal,
            durasiDetikTotal: $this->durasiDetikTotal,
            durasiDetikSection: $this->durasiDetikSection,
            sesiAktif: $this->sesiAktif,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function keArray(): array
    {
        return [
            'section' => [
                'part_ujian_id' => $this->partUjianAktifId,
                'nama' => $this->namaSection,
                'urutan' => $this->urutanSection,
                'jumlah' => $this->jumlahSection,
                'terakhir' => $this->sectionTerakhir,
                'sisa_detik' => $this->sisaDetikSection,
                'durasi_detik' => $this->durasiDetikSection,
            ],
            'total' => [
                'sisa_detik' => $this->sisaDetikTotal,
                'durasi_detik' => $this->durasiDetikTotal,
            ],
        ];
    }
}
