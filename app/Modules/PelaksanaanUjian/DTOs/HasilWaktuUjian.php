<?php

namespace App\Modules\PelaksanaanUjian\DTOs;

class HasilWaktuUjian
{
    public function __construct(
        public readonly HasilSinkronisasiWaktu $waktu,
        public readonly bool $diserahkan = false,
        public readonly ?string $pesan = null,
    ) {}

    public function sesiAktif(): bool
    {
        return $this->waktu->sesiAktif && ! $this->diserahkan;
    }

    /**
     * @return array<string, mixed>
     */
    public function keArray(): array
    {
        return [
            'sesi_aktif' => $this->sesiAktif(),
            'diserahkan' => $this->diserahkan,
            'pesan' => $this->pesan,
            'status' => $this->waktu->status->value,
            'part_ujian_aktif_id' => $this->waktu->partUjianAktifId,
            'waktu' => $this->waktu->keArray(),
        ];
    }
}
