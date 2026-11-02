<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * Periode pembukaan pendaftaran untuk satu periode pelaksanaan ujian.
 *
 * @property string $id
 * @property string $periode_ujian_id
 * @property string $nama
 * @property Carbon $dibuka_pada
 * @property Carbon $ditutup_pada
 * @property bool $aktif
 * @property string|null $catatan
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PeriodeUjian $periodeUjian
 * @property-read Collection<int, SlotUjian> $slotUjian
 */
#[Fillable([
    'periode_ujian_id',
    'nama',
    'dibuka_pada',
    'ditutup_pada',
    'aktif',
    'catatan',
])]
class PeriodePendaftaranUjian extends Model
{
    use HasUuids;

    protected $table = 'periode_pendaftaran_ujian';

    protected function casts(): array
    {
        return [
            'dibuka_pada' => 'datetime',
            'ditutup_pada' => 'datetime',
            'aktif' => 'boolean',
        ];
    }

    public function periodeUjian(): BelongsTo
    {
        return $this->belongsTo(PeriodeUjian::class, 'periode_ujian_id', 'id');
    }

    public function slotUjian(): HasManyThrough
    {
        return $this->hasManyThrough(
            SlotUjian::class,
            PeriodeUjian::class,
            'id',
            'periode_ujian_id',
            'periode_ujian_id',
            'id',
        );
    }

    public function sedangDibuka(): bool
    {
        return $this->aktif
            && now()->greaterThanOrEqualTo($this->dibuka_pada)
            && now()->lessThanOrEqualTo($this->ditutup_pada);
    }

    public function status(): string
    {
        if (! $this->aktif) {
            return 'inactive';
        }

        if (now()->lt($this->dibuka_pada)) {
            return 'upcoming';
        }

        if (now()->gt($this->ditutup_pada)) {
            return 'closed';
        }

        return 'open';
    }
}
