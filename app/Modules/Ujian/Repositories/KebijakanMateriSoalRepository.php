<?php

namespace App\Modules\Ujian\Repositories;

use App\Models\KebijakanMateriSoal;
use App\Modules\Ujian\Repositories\Contracts\KebijakanMateriSoalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class KebijakanMateriSoalRepository implements KebijakanMateriSoalRepositoryInterface
{
    public function temukanDenganKeterampilan(string $keterampilanId): ?KebijakanMateriSoal
    {
        return KebijakanMateriSoal::with('aturanMedia')
            ->where('keterampilan_id', $keterampilanId)
            ->first();
    }

    public function create(array $data): KebijakanMateriSoal
    {
        return KebijakanMateriSoal::create($data);
    }

    public function update(KebijakanMateriSoal $kebijakan, array $data): KebijakanMateriSoal
    {
        $kebijakan->update($data);

        return $kebijakan->fresh(['aturanMedia']);
    }

    public function delete(KebijakanMateriSoal $kebijakan): void
    {
        $kebijakan->delete();
    }

    public function sinkronkanAturanMedia(KebijakanMateriSoal $kebijakan, array $aturanMedia): void
    {
        $kebijakan->aturanMedia()->delete();

        foreach ($aturanMedia as $jenisMedia => $ketentuan) {
            $kebijakan->aturanMedia()->create([
                'jenis_media' => $jenisMedia,
                'ketentuan' => $ketentuan,
            ]);
        }
    }

    public function all(): Collection
    {
        return KebijakanMateriSoal::with(['aturanMedia', 'keterampilan'])
            ->orderBy('keterampilan_id')
            ->get();
    }
}
