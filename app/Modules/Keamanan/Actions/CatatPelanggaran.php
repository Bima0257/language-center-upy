<?php

namespace App\Modules\Keamanan\Actions;

use App\Enums\JenisPelanggaran;
use App\Enums\StatusSesi;
use App\Models\CatatanPelanggaran;
use App\Modules\Keamanan\DTOs\HasilPelanggaran;
use App\Modules\Keamanan\Repositories\Contracts\PelanggaranRepositoryInterface;
use App\Modules\PelaksanaanUjian\Repositories\Contracts\SesiUjianRepositoryInterface;

class CatatPelanggaran
{
    public function __construct(
        private PelanggaranRepositoryInterface $pelanggaranRepo,
        private SesiUjianRepositoryInterface $sesiRepo,
    ) {}

    public function execute(string $sesiId, string $jenis): HasilPelanggaran
    {
        $sesi = $this->sesiRepo->findOrFail($sesiId);

        if ($sesi->status !== StatusSesi::IN_PROGRESS) {
            return new HasilPelanggaran(jumlahPelanggaran: $sesi->jumlah_pelanggaran, dihentikan: false);
        }

        $jenisPelanggaran = JenisPelanggaran::tryFrom($jenis);
        $tingkat = $jenisPelanggaran?->tingkat();
        $penambahanPelanggaran = $tingkat?->penambahanPelanggaran() ?? 1;

        $terbaru = CatatanPelanggaran::where('sesi_ujian_id', $sesiId)
            ->where('jenis', $jenis)
            ->where('created_at', '>=', now()->subSeconds(5))
            ->exists();

        if ($terbaru) {
            return new HasilPelanggaran(jumlahPelanggaran: $sesi->jumlah_pelanggaran, dihentikan: false);
        }

        $jumlahBaru = $sesi->jumlah_pelanggaran + $penambahanPelanggaran;

        $this->pelanggaranRepo->create([
            'sesi_ujian_id' => $sesiId,
            'jenis' => $jenis,
            'tingkat' => $tingkat !== null ? $tingkat->value : 'minor',
            'jumlah_pelanggaran' => $jumlahBaru,
        ]);

        $dihentikan = false;
        $batasPelanggaran = $sesi->batasPelanggaranMaksimum();

        if ($jumlahBaru >= $batasPelanggaran) {
            $this->sesiRepo->update($sesiId, [
                'status' => StatusSesi::TERMINATED,
                'dihentikan_pada' => now(),
                'alasan_penghentian' => "{$batasPelanggaran} strikes violation",
                'jumlah_pelanggaran' => $jumlahBaru,
                'ditandai' => true,
                'alasan_penandaan' => "Pelanggaran mencapai {$batasPelanggaran} strike.",
            ]);
            $dihentikan = true;
        } else {
            $this->sesiRepo->update($sesiId, [
                'jumlah_pelanggaran' => $jumlahBaru,
            ]);
        }

        activity()
            ->performedOn($sesi)
            ->withProperties(['jenis' => $jenis, 'tingkat' => $tingkat?->value, 'pelanggaran' => $jumlahBaru])
            ->log("violation:{$jenis}");

        return new HasilPelanggaran(
            jumlahPelanggaran: $jumlahBaru,
            dihentikan: $dihentikan,
        );
    }
}
