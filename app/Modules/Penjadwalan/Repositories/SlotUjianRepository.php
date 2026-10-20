<?php

namespace App\Modules\Penjadwalan\Repositories;

use App\Enums\ModeUjian;
use App\Models\SlotUjian;
use App\Modules\Penjadwalan\Repositories\Contracts\SlotUjianRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class SlotUjianRepository implements SlotUjianRepositoryInterface
{
    public function findOrFail(string $id): SlotUjian
    {
        return SlotUjian::with('periodeUjian.ujian')->findOrFail($id);
    }

    public function temukanDenganPeriodeUjian(string $periodeUjianId): Collection
    {
        return SlotUjian::withCount('sesiUjian')
            ->where('periode_ujian_id', $periodeUjianId)
            ->orderBy('tanggal')
            ->orderBy('waktu_mulai')
            ->get();
    }

    public function create(array $data): SlotUjian
    {
        return SlotUjian::create($data);
    }

    public function update(SlotUjian $slotUjian, array $data): SlotUjian
    {
        $slotUjian->update($data);

        return $slotUjian->fresh();
    }

    public function delete(SlotUjian $slotUjian): void
    {
        $slotUjian->delete();
    }

    public function memilikiSesi(SlotUjian $slotUjian): bool
    {
        return $slotUjian->sesiUjian()->exists();
    }

    public function memilikiWaktuTumpangTindih(string $periodeUjianId, Carbon $tanggal, string $waktuMulai, string $waktuSelesai, ?string $kecualiId = null): bool
    {
        return SlotUjian::where('periode_ujian_id', $periodeUjianId)
            ->whereDate('tanggal', $tanggal->toDateString())
            ->when($kecualiId, fn ($q) => $q->where('id', '!=', $kecualiId))
            ->where('waktu_mulai', '<', $waktuSelesai)
            ->where('waktu_selesai', '>', $waktuMulai)
            ->exists();
    }

    public function slotTersedia(): Collection
    {
        $today = now()->toDateString();

        return SlotUjian::with('periodeUjian.ujian')
            ->where('aktif', true)
            ->whereDate('tanggal', $today)
            ->whereHas('periodeUjian.ujian', fn ($q) => $q->where('mode', ModeUjian::TRYOUT))
            ->get()
            ->filter(fn (SlotUjian $slot) => $slot->tersedia());
    }
}
