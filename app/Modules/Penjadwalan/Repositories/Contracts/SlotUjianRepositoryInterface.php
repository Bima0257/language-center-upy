<?php

namespace App\Modules\Penjadwalan\Repositories\Contracts;

use App\Models\SlotUjian;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

interface SlotUjianRepositoryInterface
{
    public function findOrFail(string $id): SlotUjian;

    public function temukanDenganPeriodeUjian(string $periodeUjianId): Collection;

    public function create(array $data): SlotUjian;

    public function update(SlotUjian $slotUjian, array $data): SlotUjian;

    public function delete(SlotUjian $slotUjian): void;

    public function memilikiSesi(SlotUjian $slotUjian): bool;

    public function memilikiWaktuTumpangTindih(string $periodeUjianId, Carbon $tanggal, string $waktuMulai, string $waktuSelesai, ?string $kecualiId = null): bool;

    public function slotTersedia(): Collection;
}
