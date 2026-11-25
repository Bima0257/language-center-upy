<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Slot waktu pelaksanaan dalam satu periode ujian.
 *
 * @property string $id
 * @property string $periode_ujian_id
 * @property Carbon $tanggal
 * @property string $waktu_mulai
 * @property string $waktu_selesai
 * @property int $toleransi_keterlambatan_menit
 * @property int $kapasitas_peserta
 * @property bool $aktif
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PeriodeUjian $periodeUjian
 * @property-read Collection<int, SesiUjian> $sesiUjian
 * @property-read Collection<int, PendaftaranUjian> $pendaftaranUjian
 */
#[Fillable([
    'periode_ujian_id', 'tanggal', 'waktu_mulai', 'waktu_selesai',
    'toleransi_keterlambatan_menit', 'kapasitas_peserta', 'aktif',
])]
class SlotUjian extends Model
{
    use HasUuids;

    protected $table = 'slot_ujian';

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'toleransi_keterlambatan_menit' => 'integer',
            'kapasitas_peserta' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function periodeUjian(): BelongsTo
    {
        return $this->belongsTo(PeriodeUjian::class, 'periode_ujian_id', 'id');
    }

    public function sesiUjian(): HasMany
    {
        return $this->hasMany(SesiUjian::class, 'slot_ujian_id', 'id');
    }

    public function pendaftaranUjian(): HasMany
    {
        return $this->hasMany(PendaftaranUjian::class, 'slot_ujian_id', 'id');
    }
}
