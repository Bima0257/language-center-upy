<?php

namespace App\Modules\Ujian\Services;

use App\Models\BankSoal;
use App\Modules\DataInduk\Repositories\Contracts\ProgramTesRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\BankSoalRepositoryInterface;
use Illuminate\Validation\ValidationException;
use Mews\Purifier\Facades\Purifier;

class LayananBankSoal
{
    public function __construct(
        private BankSoalRepositoryInterface $bankSoalRepo,
        private ProgramTesRepositoryInterface $programTesRepo,
    ) {}

    public function dataDaftar(?string $programTesId = null): array
    {
        return [
            'bankSoal' => $this->bankSoalRepo->semuaDenganProgramTesDanJumlah($programTesId),
            'programTes' => $this->programTesRepo->semuaAktifBerurutan(),
        ];
    }

    public function simpan(array $validated): void
    {
        $this->pastikanProgramTesAktif($validated['program_tes_id']);

        $this->bankSoalRepo->create($this->bersihkan($validated));
    }

    public function perbarui(BankSoal $bankSoal, array $validated): void
    {
        if ($this->bankSoalRepo->digunakanDalamUjian($bankSoal->id)) {
            throw ValidationException::withMessages([
                'bank_soal_id' => 'Bank soal tidak bisa diubah karena sudah digunakan di ujian.',
            ]);
        }

        $programTesId = $validated['program_tes_id'];

        if ($programTesId !== $bankSoal->program_tes_id) {
            $this->pastikanProgramTesAktif($programTesId);

            if ($bankSoal->soal()->exists() || $bankSoal->partUjian()->exists()) {
                throw new \RuntimeException('Bank soal tidak bisa dipindahkan ke program tes lain karena sudah digunakan.');
            }
        }

        $this->bankSoalRepo->update($bankSoal, $this->bersihkan($validated));
    }

    public function hapus(BankSoal $bankSoal): void
    {
        if ($bankSoal->soal()->exists() || $this->bankSoalRepo->digunakanDalamUjian($bankSoal->id)) {
            throw new \RuntimeException('Bank soal tidak bisa dihapus karena masih memiliki soal atau digunakan di ujian.');
        }

        $this->bankSoalRepo->delete($bankSoal);
    }

    private function bersihkan(array $data): array
    {
        $data['deskripsi'] = $data['deskripsi'] ?? null;
        if ($data['deskripsi']) {
            $data['deskripsi'] = Purifier::clean($data['deskripsi']);
        }

        return $data;
    }

    private function pastikanProgramTesAktif(string $programTesId): void
    {
        $programTes = $this->programTesRepo->find($programTesId);

        if ($programTes === null || ! $programTes->aktif) {
            throw ValidationException::withMessages([
                'program_tes_id' => 'Program tes tidak aktif atau tidak ditemukan.',
            ]);
        }
    }
}
