<?php

namespace App\Modules\Ujian\Services;

use App\Models\BankSoal;
use App\Modules\DataInduk\Repositories\Contracts\JenisTesRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\BankSoalRepositoryInterface;
use Illuminate\Validation\ValidationException;
use Mews\Purifier\Facades\Purifier;

class LayananBankSoal
{
    public function __construct(
        private BankSoalRepositoryInterface $bankSoalRepo,
        private JenisTesRepositoryInterface $jenisTesRepo,
    ) {}

    public function dataDaftar(?string $jenisTesId = null): array
    {
        return [
            'bankSoal' => $this->bankSoalRepo->semuaDenganJenisTesDanJumlah($jenisTesId),
            'jenisTes' => $this->jenisTesRepo->semuaAktifBerurutan(),
        ];
    }

    public function simpan(array $validated): void
    {
        $this->pastikanJenisTesAktif($validated['jenis_tes_id']);

        $this->bankSoalRepo->create($this->bersihkan($validated));
    }

    public function perbarui(BankSoal $bankSoal, array $validated): void
    {
        $jenisTesId = $validated['jenis_tes_id'];

        if ($jenisTesId !== $bankSoal->jenis_tes_id) {
            $this->pastikanJenisTesAktif($jenisTesId);

            if ($bankSoal->soal()->exists() || $bankSoal->bagianUjian()->exists()) {
                throw new \RuntimeException('Bank soal tidak bisa dipindahkan ke jenis tes lain karena sudah digunakan.');
            }
        }

        $this->bankSoalRepo->update($bankSoal, $this->bersihkan($validated));
    }

    public function hapus(BankSoal $bankSoal): void
    {
        if ($bankSoal->soal()->exists() || $bankSoal->bagianUjian()->exists()) {
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

    private function pastikanJenisTesAktif(string $jenisTesId): void
    {
        $jenisTes = $this->jenisTesRepo->find($jenisTesId);

        if ($jenisTes === null || ! $jenisTes->aktif) {
            throw ValidationException::withMessages([
                'jenis_tes_id' => 'Jenis tes tidak aktif atau tidak ditemukan.',
            ]);
        }
    }
}
